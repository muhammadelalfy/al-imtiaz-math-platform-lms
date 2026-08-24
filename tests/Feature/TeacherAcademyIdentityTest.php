<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAcademyIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_read_and_rename_only_their_own_academy_identity(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'الامتياز',
            'slug' => 'al-imtiaz-teacher',
        ]);
        $otherTenant = Tenant::query()->create([
            'name' => 'أكاديمية أخرى',
            'slug' => 'other-academy',
        ]);
        $teacher = User::factory()->create(['role' => 'teacher', 'tenant_id' => $tenant->id]);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->getJson('/api/teacher/academy-identity')
            ->assertOk()
            ->assertJsonPath('academy_name', 'الامتياز');

        $this->putJson('/api/teacher/academy-identity', ['academy_name' => 'الامتياز الحديثة'])
            ->assertOk()
            ->assertJsonPath('academy_name', 'الامتياز الحديثة');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'الامتياز الحديثة']);
        $this->assertDatabaseHas('tenants', ['id' => $otherTenant->id, 'name' => 'أكاديمية أخرى']);
    }

    public function test_teacher_academy_identity_requires_a_valid_name_and_a_teacher_owned_tenant(): void
    {
        $tenant = Tenant::query()->create(['name' => 'الامتياز', 'slug' => 'al-imtiaz-validation']);
        $teacher = User::factory()->create(['role' => 'teacher', 'tenant_id' => $tenant->id]);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->putJson('/api/teacher/academy-identity', ['academy_name' => 'أ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('academy_name');

        $parent = User::factory()->create(['role' => 'parent', 'tenant_id' => $tenant->id]);
        Sanctum::actingAs($parent, ['guard:parent']);
        $this->getJson('/api/teacher/academy-identity')->assertForbidden();
    }

    public function test_authenticated_teacher_identity_includes_their_academy_name_without_serializing_tenant_details(): void
    {
        $tenant = Tenant::query()->create(['name' => 'الامتياز', 'slug' => 'al-imtiaz-identity']);
        $teacher = User::factory()->create(['role' => 'teacher', 'tenant_id' => $tenant->id]);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('academy_name', 'الامتياز')
            ->assertJsonMissing(['tenant']);
    }
}
