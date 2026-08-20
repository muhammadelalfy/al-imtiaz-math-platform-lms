<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PluginPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'label', 'recipient', 'instructions', 'is_enabled', 'configured_by'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PluginPaymentTransaction::class);
    }
}
