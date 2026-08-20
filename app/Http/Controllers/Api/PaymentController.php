<?php
namespace App\Http\Controllers\Api;
use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesStaff;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
class PaymentController extends Controller {
    use AuthorizesStaff;
    public function __construct(private readonly DashboardMetricsCacheInterface $metricsCache) {}
    public function index(Request $request) { $query=Payment::with('student')->latest('due_at'); $this->scope($query,$request); return PaymentResource::collection($query->paginate(50)); }
    public function store(StorePaymentRequest $request) { $payment=Payment::create([...$request->validated(),'recorded_by'=>$request->user()->id])->load('student'); $this->metricsCache->forget(); return (new PaymentResource($payment))->response()->setStatusCode(201); }
    public function update(UpdatePaymentRequest $request, Payment $payment) { $payment->update($request->validated()); $this->metricsCache->forget(); return new PaymentResource($payment->fresh('student')); }
    public function destroy(Request $request, Payment $payment) { $this->authorizeStaff($request); $payment->delete(); $this->metricsCache->forget(); return response()->noContent(); }
    private function scope($query, Request $request): void { if ($request->user()->isAnyRole('student','parent')) { $account=$request->user()->loadMissing('studentAccount')->studentAccount; abort_unless($account !== null,403); $query->where('student_id',$account->student_id); } }
}
