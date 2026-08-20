<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'due_at' => $this->due_at,
            'paid_at' => $this->paid_at,
            'note' => $this->note,
            'recorded_by' => $this->recorded_by,
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
        ];
    }
}
