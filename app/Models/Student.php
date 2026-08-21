<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'group', 'grade', 'phone', 'parent_phone', 'status'];

    protected $hidden = ['qr_token'];

    public function ensureQrToken(): string
    {
        $token = $this->getAttribute('qr_token');
        if (! is_string($token) || $token === '') {
            $this->forceFill(['qr_token' => Str::random(64)])->save();
        }

        return (string) $this->getAttribute('qr_token');
    }

    public function account(): HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorksheetAssignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function academicGroups(): BelongsToMany
    {
        return $this->belongsToMany(AcademicGroup::class, 'academic_group_student')->withTimestamps();
    }
}
