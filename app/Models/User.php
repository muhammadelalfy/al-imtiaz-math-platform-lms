<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name
 * @property string $email
 * @property string $role
 * @property-read StudentAccount|null $studentAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AuthorizationRole> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AuthorizationPermission> $permissions
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'tenant_id', 'is_super_admin'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function studentAccount(): HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function pluginPurchases(): HasMany
    {
        return $this->hasMany(PluginPurchase::class);
    }

    public function pluginPaymentTransactions(): HasMany
    {
        return $this->hasMany(PluginPaymentTransaction::class);
    }

    public function installedModules(): HasMany
    {
        return $this->hasMany(InstalledModule::class, 'installed_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAnyRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Roles and permissions describe authorization across all account guards.
     * Guards still separate login entry points and token abilities; duplicating
     * every permission for each of those guards would add no security value.
     */
    public function guardName(): string
    {
        return 'web';
    }
}
