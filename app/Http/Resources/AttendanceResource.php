<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AttendanceRecord */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'attendance_date' => $this->attendance_date,
            'date_at' => $this->date_at,
            'status' => $this->status,
            'note' => $this->note,
            'recorded_by' => $this->recorded_by,
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
        ];
    }
}
