<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\QuestionBankQuestion */
class QuestionBankQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'type' => $this->type,
            'title' => $this->title,
            'grade' => $this->grade,
            'prompt_html' => $this->prompt_html,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'points' => $this->points,
            'tags' => $this->tags,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'department' => $this->whenLoaded('department', fn () => new ExamDepartmentResource($this->department)),
        ];
    }
}
