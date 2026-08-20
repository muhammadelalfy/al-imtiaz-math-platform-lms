<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'tagline', 'description', 'price_cents', 'currency',
        'duration_days', 'teacher_limit', 'student_limit', 'features', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['features' => 'array', 'is_active' => 'boolean'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
