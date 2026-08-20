<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSlackLogDestination extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'channel_label', 'webhook_url', 'is_enabled'];

    protected $hidden = ['webhook_url'];

    protected function casts(): array
    {
        return [
            'webhook_url' => 'encrypted',
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
