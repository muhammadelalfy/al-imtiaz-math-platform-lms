<?php

namespace Tests\Feature;

use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Contracts\Services\TenantSchemaProvisionerInterface;
use App\Services\TenantDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_and_teacher_registration_create_a_pending_tenant_subscription(): void
    {
        $package = $this->package();

        $this->getJson('/api/public/subscription-packages')
            ->assertOk()
            ->assertJsonFragment(['id' => $package->id, 'name' => $package->name]);

        $this->postJson('/api/public/teacher-register', [
            'name' => 'أستاذة منى',
            'email' => 'mona@example.test',
            'password' => 'TeacherSecure!2026',
            'password_confirmation' => 'TeacherSecure!2026',
            'organization_name' => 'مركز منى للرياضيات',
            'tenant_slug' => 'mona-math',
            'package_id' => $package->id,
        ])->assertCreated()
            ->assertJsonPath('user.role', 'teacher');

        $this->assertDatabaseHas('tenants', ['slug' => 'mona-math']);
        $this->assertDatabaseHas('tenant_subscriptions', ['status' => 'pending', 'payment_status' => 'unpaid']);
    }

    public function test_only_super_admin_can_view_platform_health_and_activate_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $ordinaryAdmin = User::factory()->create(['role' => 'admin', 'is_super_admin' => false]);
        $tenant = Tenant::query()->create(['name' => 'مركز الحساب', 'slug' => 'account-center']);
        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id, 'subscription_package_id' => $this->package()->id,
            'status' => 'pending', 'payment_status' => 'unpaid',
        ]);

        Sanctum::actingAs($ordinaryAdmin);
        $this->getJson('/api/super-admin/overview')->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/super-admin/overview')
            ->assertOk()
            ->assertJsonPath('health.database', 'healthy');
        $this->putJson("/api/super-admin/tenants/{$tenant->id}/domain", [
            'login_domain' => 'account.example.test',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('login_domain');
        $this->postJson('/api/super-admin/packages', [
            'code' => 'pro', 'name' => 'الاحتراف', 'tagline' => 'للمراكز المتقدمة',
            'price_cents' => 149000, 'currency' => 'EGP', 'duration_days' => 30,
            'teacher_limit' => 10, 'student_limit' => 1000, 'features' => ['إدارة متقدمة'],
            'is_active' => true, 'sort_order' => 40,
        ])->assertCreated()
            ->assertJsonPath('code', 'pro');
        $this->putJson("/api/super-admin/subscriptions/{$subscription->id}", [
            'status' => 'active', 'payment_status' => 'paid', 'payment_reference' => 'SUB-2026-0001',
        ])->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('payment_status', 'paid');
        $this->putJson("/api/super-admin/tenants/{$tenant->id}/domain", [
            'login_domain' => 'account.example.test',
        ])->assertOk()
            ->assertJsonPath('login_domain', 'account.example.test');

        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'active', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('subscription_packages', ['code' => 'pro']);
    }

    public function test_teacher_receives_one_idempotent_expiry_reminder_on_login_dashboard_load(): void
    {
        $tenant = Tenant::query()->create(['name' => 'مركز التنبيه', 'slug' => 'expiry-center']);
        $teacher = User::factory()->create(['role' => 'teacher', 'tenant_id' => $tenant->id]);
        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id, 'subscription_package_id' => $this->package()->id,
            'status' => 'active', 'payment_status' => 'paid',
            'starts_at' => now()->subDays(25), 'ends_at' => now()->addDays(5),
        ]);

        Sanctum::actingAs($teacher, ['guard:teacher']);
        $this->getJson('/api/teacher/subscription')
            ->assertOk()
            ->assertJsonPath('show_expiry_reminder', true)
            ->assertJsonPath('subscription.days_remaining', 5);
        $this->getJson('/api/teacher/subscription')
            ->assertOk()
            ->assertJsonPath('show_expiry_reminder', false);

        $this->assertNotNull($subscription->refresh()->reminder_shown_at);
    }

    public function test_tenant_domain_login_rejects_a_teacher_from_another_center(): void
    {
        $expectedTenant = Tenant::query()->create([
            'name' => 'مركز النطاق', 'slug' => 'domain-center', 'login_domain' => 'center.example.test', 'domain_status' => 'active',
        ]);
        $otherTenant = Tenant::query()->create(['name' => 'مركز آخر', 'slug' => 'other-center']);
        User::factory()->create([
            'role' => 'teacher', 'tenant_id' => $otherTenant->id, 'email' => 'teacher@other.test',
            'password' => Hash::make('TeacherSecure!2026'),
        ]);

        $this->expectException(ValidationException::class);
        app(TenantDomainService::class)->assertLoginMatchesTenant(
            Request::create("https://{$expectedTenant->login_domain}/api/auth/teacher/login"),
            User::query()->where('email', 'teacher@other.test')->firstOrFail(),
        );
    }

    public function test_paid_subscription_activation_invokes_the_tenant_schema_provisioner(): void
    {
        $provisioner = new class implements TenantSchemaProvisionerInterface
        {
            public int $provisioned = 0;

            public function provision(Tenant $tenant): Tenant
            {
                $this->provisioned++;

                return $tenant;
            }

            public function isReady(Tenant $tenant): bool
            {
                return false;
            }
        };
        $this->app->instance(TenantSchemaProvisionerInterface::class, $provisioner);
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $tenant = Tenant::query()->create(['name' => 'مركز التهيئة', 'slug' => 'provisioning-center']);
        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_package_id' => $this->package()->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/super-admin/subscriptions/{$subscription->id}", [
            'status' => 'active',
            'payment_status' => 'paid',
        ])->assertOk();

        $this->assertSame(1, $provisioner->provisioned);
    }

    public function test_schema_ready_tenant_receives_a_deterministic_pending_login_domain(): void
    {
        config(['tenancy.domain_base' => 'centres.example.test']);
        $tenant = Tenant::query()->create([
            'name' => 'مركز النطاق التلقائي',
            'slug' => 'automatic-domain',
            'database_schema' => 'tenant_1',
            'schema_status' => 'ready',
        ]);

        $resolved = app(TenantDomainService::class)->assignSubscriptionDomain($tenant);

        $this->assertSame('automatic-domain.centres.example.test', $resolved->login_domain);
        $this->assertSame('pending_dns', $resolved->domain_status);
    }

    private function package(): SubscriptionPackage
    {
        return SubscriptionPackage::query()->firstOrCreate(['code' => 'growth'], [
            'name' => 'باقة النمو', 'price_cents' => 99000, 'currency' => 'EGP', 'duration_days' => 30,
            'teacher_limit' => 5, 'student_limit' => 500, 'features' => ['تقارير متقدمة'], 'is_active' => true,
        ]);
    }
}
