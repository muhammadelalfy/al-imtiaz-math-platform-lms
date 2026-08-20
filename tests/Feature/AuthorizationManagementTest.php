<?php

namespace Tests\Feature;

use App\Models\AuthorizationPermission;
use App\Models\AuthorizationRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_teacher_has_a_dedicated_login_guard_and_administrators_cannot_use_it(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher.guard@example.test',
            'password' => Hash::make('TeacherPass!2026'),
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.guard@example.test',
            'password' => Hash::make('AdminPass!2026'),
        ]);

        $this->postJson('/api/auth/teacher/login', [
            'email' => $teacher->email,
            'password' => 'TeacherPass!2026',
        ])->assertOk()
            ->assertJsonPath('user.id', $teacher->id)
            ->assertJsonPath('login_type', 'teacher')
            ->assertJsonStructure(['token']);

        $this->postJson('/api/auth/teacher/login', [
            'email' => $admin->email,
            'password' => 'AdminPass!2026',
        ])->assertUnprocessable();
    }

    public function test_authorized_teacher_can_create_custom_permissions_and_roles_but_not_change_system_permissions(): void
    {
        $teacher = $this->authorizationManagerTeacher();
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $customPermission = $this->postJson('/api/staff/authorization/permissions', [
            'name' => 'worksheets.review',
            'label' => 'مراجعة الشيتات',
            'description' => 'مراجعة مسودات الشيتات قبل النشر.',
        ])->assertCreated()
            ->assertJsonPath('name', 'worksheets.review')
            ->assertJsonPath('is_system', false)
            ->json();

        $this->postJson('/api/staff/authorization/roles', [
            'name' => 'worksheet-reviewer',
            'label' => 'مراجع الشيتات',
            'permission_ids' => [$customPermission['id']],
        ])->assertCreated()
            ->assertJsonPath('name', 'worksheet-reviewer')
            ->assertJsonPath('permission_ids.0', $customPermission['id']);

        $systemPermission = AuthorizationPermission::query()->create([
            'name' => 'reports.read',
            'guard_name' => 'web',
            'label' => 'عرض التقارير',
            'is_system' => true,
        ]);

        $this->putJson("/api/staff/authorization/permissions/{$systemPermission->id}", [
            'label' => 'محاولة تعديل محمي',
        ])->assertForbidden();
    }

    public function test_student_cannot_access_staff_authorization_catalog(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student, ['guard:student']);

        $this->getJson('/api/staff/authorization/catalog')->assertForbidden();
    }

    public function test_administrator_can_assign_only_custom_roles_to_staff_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $customRole = AuthorizationRole::factory()->create();
        $systemRole = AuthorizationRole::factory()->create(['is_system' => true]);
        Sanctum::actingAs($admin, ['guard:admin']);

        $this->putJson("/api/staff/authorization/staff/{$teacher->id}/roles", [
            'role_ids' => [$customRole->id],
        ])->assertOk()
            ->assertJsonPath('id', $teacher->id)
            ->assertJsonPath('role_ids.0', $customRole->id);

        $this->putJson("/api/staff/authorization/staff/{$teacher->id}/roles", [
            'role_ids' => [$systemRole->id],
        ])->assertUnprocessable();
    }

    private function authorizationManagerTeacher(): User
    {
        $permission = AuthorizationPermission::query()->create([
            'name' => 'authorization.manage',
            'guard_name' => 'web',
            'label' => 'إدارة الصلاحيات',
            'is_system' => true,
        ]);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->givePermissionTo($permission);

        return $teacher;
    }
}
