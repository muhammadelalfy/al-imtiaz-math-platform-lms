<?php

namespace Tests\Feature;

use App\Models\PluginPaymentTransaction;
use App\Models\PluginProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PluginPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_egyptian_methods_and_customers_can_submit_a_fawry_reference(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'teacher']);
        $plugin = $this->paidPlugin();

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/plugin-payment-methods/fawry', [
            'recipient' => 'Fawry merchant code 77881',
            'instructions' => 'ادفع في أقرب منفذ فوري ثم اكتب الرقم المرجعي.',
            'is_enabled' => true,
        ])->assertOk()
            ->assertJsonPath('code', 'fawry')
            ->assertJsonPath('is_enabled', true);

        Sanctum::actingAs($buyer);
        $this->getJson('/api/plugin-payment-methods')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'fawry')
            ->assertJsonPath('data.0.recipient', 'Fawry merchant code 77881');

        $checkout = $this->postJson("/api/plugins/{$plugin->id}/checkout", ['payment_method' => 'fawry'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('amount', '150.00')
            ->assertJsonPath('method.code', 'fawry');

        $paymentId = $checkout->json('id');
        $this->postJson("/api/plugin-payments/{$paymentId}/reference", [
            'reference' => 'FAWRY-2026-0001',
            'customer_note' => 'تم الدفع من فرع مدينة نصر',
        ])->assertOk()
            ->assertJsonPath('status', 'submitted')
            ->assertJsonPath('reference', 'FAWRY-2026-0001');

        $this->assertDatabaseHas('plugin_payment_transactions', [
            'id' => $paymentId,
            'status' => 'submitted',
            'reference' => 'FAWRY-2026-0001',
        ]);
        $this->assertDatabaseMissing('plugin_purchases', ['user_id' => $buyer->id, 'plugin_product_id' => $plugin->id]);
    }

    public function test_only_admin_can_review_manual_payment_and_approval_fulfills_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'teacher']);
        $plugin = $this->paidPlugin();

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/plugin-payment-methods/vodafone_cash', [
            'recipient' => '01000000000',
            'instructions' => 'حوّل المبلغ ثم أرسل المرجع.',
            'is_enabled' => true,
        ])->assertOk();

        Sanctum::actingAs($buyer);
        $checkout = $this->postJson("/api/plugins/{$plugin->id}/checkout", ['payment_method' => 'vodafone_cash'])->assertCreated();
        $paymentId = $checkout->json('id');
        $this->postJson("/api/plugin-payments/{$paymentId}/reference", ['reference' => 'VC-2026-0099'])->assertOk();

        $this->getJson('/api/admin/plugin-payments/review-queue')->assertForbidden();
        $this->postJson("/api/admin/plugin-payments/{$paymentId}/approve")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/plugin-payments/review-queue')
            ->assertOk()
            ->assertJsonPath('data.0.id', $paymentId)
            ->assertJsonPath('data.0.user.id', $buyer->id);

        $this->postJson("/api/admin/plugin-payments/{$paymentId}/approve", ['review_note' => 'تمت مطابقة عملية التحويل.'])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('plugin_purchases', [
            'user_id' => $buyer->id,
            'plugin_product_id' => $plugin->id,
            'status' => 'completed',
        ]);
        $this->assertSame(1, PluginPaymentTransaction::query()->where('id', $paymentId)->where('status', 'approved')->count());
        $this->postJson("/api/admin/plugin-payments/{$paymentId}/approve")->assertUnprocessable();
        $this->assertSame(1, $buyer->pluginPurchases()->where('plugin_product_id', $plugin->id)->count());
    }

    private function paidPlugin(): PluginProduct
    {
        return PluginProduct::create([
            'slug' => 'paid-payment-plugin-'.PluginProduct::query()->count(),
            'name' => 'إضافة مدفوعة',
            'version' => '1.0.0',
            'module_name' => 'PaidPaymentPlugin'.PluginProduct::query()->count(),
            'artifact_path' => 'plugins/artifacts/paid.zip',
            'price' => 150,
            'is_active' => true,
        ]);
    }
}
