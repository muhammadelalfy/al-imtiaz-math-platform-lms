<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'students' => $this->resource['students'],
            'attendance' => $this->resource['attendance'],
            'exams' => $this->resource['exams'],
            'payments' => $this->resource['payments'],
        ];
    }
}
