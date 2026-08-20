<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginPaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plugin_product_id', 'plugin_payment_method_id', 'status', 'amount', 'currency',
        'reference', 'customer_note', 'reviewed_by', 'reviewed_at', 'review_note', 'fulfilled_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime', 'fulfilled_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(PluginProduct::class, 'plugin_product_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PluginPaymentMethod::class, 'plugin_payment_method_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
