<?php

namespace App\Http\Requests;

class UpdateQuestionBankQuestionRequest extends StoreQuestionBankQuestionRequest
{
    public function rules(): array
    {
        return $this->questionRules(true);
    }
}
