<?php

namespace App\Models;

use Database\Factories\AuthorizationRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role;

/**
 * @property string $name
 * @property string $guard_name
 * @property string|null $label
 * @property string|null $description
 * @property bool $is_system
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AuthorizationPermission> $permissions
 */
class AuthorizationRole extends Role
{
    /** @use HasFactory<AuthorizationRoleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    protected static function newFactory(): AuthorizationRoleFactory
    {
        return AuthorizationRoleFactory::new();
    }
}
