<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExamResult */
class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'title' => $this->title,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'taken_at' => $this->taken_at,
            'recorded_by' => $this->recorded_by,
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
        ];
    }
}
