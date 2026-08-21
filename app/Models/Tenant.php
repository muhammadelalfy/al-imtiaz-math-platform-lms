<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'login_domain',
        'domain_status',
        'database_schema',
        'schema_status',
        'schema_version',
        'schema_provisioned_at',
        'provisioning_error',
    ];

    protected function casts(): array
    {
        return [
            'schema_provisioned_at' => 'immutable_datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
