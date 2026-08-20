<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorksheetAssignment */
class WorksheetAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worksheet_id' => $this->worksheet_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'feedback' => $this->feedback,
            'assigned_at' => $this->assigned_at,
            'submitted_at' => $this->submitted_at,
            'worksheet' => $this->whenLoaded('worksheet', fn () => new WorksheetResource($this->worksheet)),
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
        ];
    }
}
