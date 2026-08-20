<?php

namespace App\Models;

use Database\Factories\OfflineSyncOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncOperation extends Model
{
    /** @use HasFactory<OfflineSyncOperationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_operation_id',
        'type',
        'status',
        'payload',
        'result',
        'error_code',
        'occurred_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
