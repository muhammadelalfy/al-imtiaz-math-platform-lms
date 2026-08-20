<?php

namespace App\Models;

use Database\Factories\AcademicGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property string $grade
 * @property string $name
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Student> $students
 */
class AcademicGroup extends Model
{
    /** @use HasFactory<AcademicGroupFactory> */
    use HasFactory;

    protected $fillable = ['grade', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'academic_group_student')->withTimestamps();
    }

    public function notificationCampaigns(): HasMany
    {
        return $this->hasMany(NotificationCampaign::class);
    }
}
