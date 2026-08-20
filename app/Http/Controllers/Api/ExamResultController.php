<?php
namespace App\Http\Controllers\Api;
use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesStaff;
use App\Http\Requests\StoreExamResultRequest;
use App\Http\Requests\UpdateExamResultRequest;
use App\Http\Resources\ExamResultResource;
use App\Models\ExamResult;
use Illuminate\Http\Request;
class ExamResultController extends Controller {
    use AuthorizesStaff;
    public function __construct(private readonly DashboardMetricsCacheInterface $metricsCache) {}
    public function index(Request $request) { $query=ExamResult::with('student')->latest('taken_at'); $this->scope($query,$request); return ExamResultResource::collection($query->paginate(50)); }
    public function store(StoreExamResultRequest $request) { $exam=ExamResult::create([...$request->validated(),'recorded_by'=>$request->user()->id])->load('student'); $this->metricsCache->forget(); return (new ExamResultResource($exam))->response()->setStatusCode(201); }
    public function update(UpdateExamResultRequest $request, ExamResult $exam) { $exam->update($request->validated()); $this->metricsCache->forget(); return new ExamResultResource($exam->fresh('student')); }
    public function destroy(Request $request, ExamResult $exam) { $this->authorizeStaff($request); $exam->delete(); $this->metricsCache->forget(); return response()->noContent(); }
    private function scope($query, Request $request): void { if ($request->user()->isAnyRole('student','parent')) { $account=$request->user()->loadMissing('studentAccount')->studentAccount; abort_unless($account,403); $query->where('student_id',$account->student_id); } }
}
