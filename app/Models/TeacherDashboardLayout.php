<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDashboardLayout extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'card_order'];

    protected function casts(): array
    {
        return ['card_order' => 'array'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
