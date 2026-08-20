<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExamTemplate */
class ExamTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'title' => $this->title,
            'grade' => $this->grade,
            'duration_minutes' => $this->duration_minutes,
            'instructions' => $this->instructions,
            'watermark_text' => $this->watermark_text,
            'watermark_opacity' => $this->watermark_opacity,
            'print_header' => $this->print_header,
            'print_footer' => $this->print_footer,
            'status' => $this->status,
            'department' => $this->whenLoaded('department', fn () => new ExamDepartmentResource($this->department)),
            'questions' => ExamQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
