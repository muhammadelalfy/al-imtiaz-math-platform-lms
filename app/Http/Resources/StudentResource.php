<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'group' => $this->group,
            'grade' => $this->grade,
            'phone' => $this->phone,
            'parent_phone' => $this->parent_phone,
            'status' => $this->status,
            'assignments_count' => $this->whenCounted('assignments'),
            'attendance_records_count' => $this->whenCounted('attendanceRecords'),
            'exam_results_count' => $this->whenCounted('examResults'),
            'payments_count' => $this->whenCounted('payments'),
            'assignments' => $this->whenLoaded('assignments'),
            'attendance_records' => $this->whenLoaded('attendanceRecords'),
            'exam_results' => $this->whenLoaded('examResults'),
            'payments' => $this->whenLoaded('payments'),
        ];
    }
}
