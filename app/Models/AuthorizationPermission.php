<?php

namespace App\Models;

use Database\Factories\AuthorizationPermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;

/**
 * @property string $name
 * @property string $guard_name
 * @property string|null $label
 * @property string|null $description
 * @property bool $is_system
 */
class AuthorizationPermission extends Permission
{
    /** @use HasFactory<AuthorizationPermissionFactory> */
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

    protected static function newFactory(): AuthorizationPermissionFactory
    {
        return AuthorizationPermissionFactory::new();
    }
}
