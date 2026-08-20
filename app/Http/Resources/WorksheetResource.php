<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Worksheet */
class WorksheetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'grade' => $this->grade,
            'instructions' => $this->instructions,
            'due_at' => $this->due_at,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'assignments_count' => $this->whenCounted('assignments'),
            'submitted_count' => $this->whenCounted('submitted'),
            'assignments' => WorksheetAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }
}
