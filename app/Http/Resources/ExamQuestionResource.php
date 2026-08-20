<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExamQuestion */
class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'prompt_html' => $this->prompt_html,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'points' => $this->points,
            'sort_order' => $this->sort_order,
        ];
    }
}
