<?php
namespace App\Http\Controllers\Api;
use App\Contracts\Repositories\DashboardMetricsRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReportSummaryResource;
use Illuminate\Http\Request;
class ReportController extends Controller {
    public function __construct(private readonly DashboardMetricsRepositoryInterface $metrics) {}
    public function summary(Request $request) { abort_unless($request->user()->isAnyRole('admin','teacher'),403); return new ReportSummaryResource($this->metrics->summary()); }
}
