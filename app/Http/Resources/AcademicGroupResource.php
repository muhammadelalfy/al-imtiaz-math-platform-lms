<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AcademicGroup */
class AcademicGroupResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grade' => $this->grade,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'students_count' => $this->whenCounted('students'),
            'students' => $this->whenLoaded('students', fn () => $this->students->map(fn ($student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'grade' => $student->grade,
                'group' => $student->group,
            ])->values()),
        ];
    }
}
