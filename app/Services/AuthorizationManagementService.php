<?php

namespace App\Services;

use App\Models\AuthorizationPermission;
use App\Models\AuthorizationRole;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class AuthorizationManagementService
{
    public const AUTHORIZATION_GUARD = 'web';

    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @return array{permissions: Collection<int, AuthorizationPermission>, roles: Collection<int, AuthorizationRole>, staff: Collection<int, User>}
     */
    public function catalog(): array
    {
        return [
            'permissions' => AuthorizationPermission::query()->orderBy('is_system', 'desc')->orderBy('label')->get(),
            'roles' => AuthorizationRole::query()->with('permissions')->orderBy('is_system', 'desc')->orderBy('label')->get(),
            'staff' => User::query()->whereIn('role', ['admin', 'teacher'])->with('roles.permissions')->orderBy('name')->get(),
        ];
    }

    /** @param array{name: string, label: string, description?: string|null} $attributes */
    public function createPermission(array $attributes): AuthorizationPermission
    {
        return AuthorizationPermission::query()->create([
            ...$attributes,
            'guard_name' => self::AUTHORIZATION_GUARD,
            'is_system' => false,
        ]);
    }

    /** @param array{name?: string, label?: string, description?: string|null} $attributes */
    public function updatePermission(AuthorizationPermission $permission, array $attributes): AuthorizationPermission
    {
        $this->assertMutablePermission($permission);
        $permission->fill($attributes)->save();

        return $permission->refresh();
    }

    public function deletePermission(AuthorizationPermission $permission): void
    {
        $this->assertMutablePermission($permission);
        $permission->delete();
    }

    /** @param array{name: string, label: string, description?: string|null, permission_ids: array<int, int>} $attributes */
    public function createRole(User $actor, array $attributes): AuthorizationRole
    {
        return $this->database->transaction(function () use ($actor, $attributes): AuthorizationRole {
            $role = AuthorizationRole::query()->create([
                'name' => $attributes['name'],
                'label' => $attributes['label'],
                'description' => $attributes['description'] ?? null,
                'guard_name' => self::AUTHORIZATION_GUARD,
                'is_system' => false,
            ]);
            $role->syncPermissions($this->selectPermissions($actor, $attributes['permission_ids']));

            return $role->load('permissions');
        });
    }

    /** @param array{name?: string, label?: string, description?: string|null, permission_ids?: array<int, int>} $attributes */
    public function updateRole(User $actor, AuthorizationRole $role, array $attributes): AuthorizationRole
    {
        $this->assertMutableRole($role);

        return $this->database->transaction(function () use ($actor, $role, $attributes): AuthorizationRole {
            $role->fill(collect($attributes)->except('permission_ids')->all())->save();
            if (array_key_exists('permission_ids', $attributes)) {
                $role->syncPermissions($this->selectPermissions($actor, $attributes['permission_ids']));
            }

            return $role->refresh()->load('permissions');
        });
    }

    public function deleteRole(AuthorizationRole $role): void
    {
        $this->assertMutableRole($role);
        $role->delete();
    }

    /** @param array<int, int> $roleIds */
    public function syncStaffRoles(User $actor, User $staffMember, array $roleIds): User
    {
        abort_unless($staffMember->isAnyRole('admin', 'teacher'), 422, 'يمكن إسناد الأدوار المخصصة للعاملين فقط.');
        abort_if(!$actor->isAnyRole('admin') && $staffMember->isAnyRole('admin'), 403);

        return $this->database->transaction(function () use ($actor, $staffMember, $roleIds): User {
            $selected = AuthorizationRole::query()
                ->where('guard_name', self::AUTHORIZATION_GUARD)
                ->where('is_system', false)
                ->whereKey($roleIds)
                ->get();

            abort_unless($selected->count() === count(array_unique($roleIds)), 422, 'لا يمكن إسناد دور نظامي أو غير موجود.');

            $existingSystem = $staffMember->roles()->where('is_system', true)->get();
            abort_if(!$actor->isAnyRole('admin') && $existingSystem->isNotEmpty(), 403);

            $staffMember->syncRoles($existingSystem->merge($selected));

            return $staffMember->refresh()->load('roles.permissions');
        });
    }

    /** @param array<int, int> $permissionIds */
    private function selectPermissions(User $actor, array $permissionIds): Collection
    {
        $permissions = AuthorizationPermission::query()
            ->where('guard_name', self::AUTHORIZATION_GUARD)
            ->whereKey($permissionIds)
            ->get();

        abort_unless($permissions->count() === count(array_unique($permissionIds)), 422, 'تتضمن الصلاحيات عنصراً غير متاح.');
        abort_if(!$actor->isAnyRole('admin') && $permissions->contains('is_system', true), 403);

        return $permissions;
    }

    private function assertMutablePermission(AuthorizationPermission $permission): void
    {
        abort_if($permission->is_system, 403, 'لا يمكن تعديل صلاحية نظامية من لوحة العاملين.');
    }

    private function assertMutableRole(AuthorizationRole $role): void
    {
        abort_if($role->is_system, 403, 'لا يمكن تعديل دور نظامي من لوحة العاملين.');
    }
}
