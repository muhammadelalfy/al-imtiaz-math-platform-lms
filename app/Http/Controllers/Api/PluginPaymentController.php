<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewPluginPaymentRequest;
use App\Http\Requests\StartPluginCheckoutRequest;
use App\Http\Requests\SubmitPluginPaymentReferenceRequest;
use App\Http\Requests\UpdatePluginPaymentMethodRequest;
use App\Http\Resources\PluginPaymentMethodResource;
use App\Http\Resources\PluginPaymentTransactionResource;
use App\Models\PluginPaymentTransaction;
use App\Models\PluginProduct;
use App\Services\PluginPaymentService;
use Illuminate\Http\Request;

class PluginPaymentController extends Controller
{
    public function __construct(private readonly PluginPaymentService $payments)
    {
    }

    public function methods()
    {
        return response()->json([
            'data' => PluginPaymentMethodResource::collection($this->payments->enabledMethods())->resolve(),
        ]);
    }

    public function checkout(StartPluginCheckoutRequest $request, PluginProduct $plugin)
    {
        return new PluginPaymentTransactionResource(
            $this->payments->begin($request->user(), $plugin, $request->validated('payment_method')),
        );
    }

    public function history(Request $request)
    {
        $payments = PluginPaymentTransaction::query()
            ->with(['plugin', 'method'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        return PluginPaymentTransactionResource::collection($payments);
    }

    public function submitReference(SubmitPluginPaymentReferenceRequest $request, PluginPaymentTransaction $payment)
    {
        return new PluginPaymentTransactionResource($this->payments->submitReference(
            $payment,
            $request->user(),
            $request->validated('reference'),
            $request->validated('customer_note'),
        ));
    }

    public function adminMethods(Request $request)
    {
        abort_unless($request->user()->isAnyRole('admin'), 403);

        return response()->json([
            'data' => PluginPaymentMethodResource::collection($this->payments->allMethods())->resolve(),
        ]);
    }

    public function updateMethod(UpdatePluginPaymentMethodRequest $request, string $method)
    {
        return (new PluginPaymentMethodResource(
            $this->payments->configure($method, $request->validated(), $request->user()),
        ))->response()->setStatusCode(200);
    }

    public function reviewQueue(Request $request)
    {
        abort_unless($request->user()->isAnyRole('admin'), 403);

        return PluginPaymentTransactionResource::collection(
            PluginPaymentTransaction::query()->with(['plugin', 'method', 'user'])->where('status', 'submitted')->latest()->paginate(50),
        );
    }

    public function approve(ReviewPluginPaymentRequest $request, PluginPaymentTransaction $payment)
    {
        return new PluginPaymentTransactionResource($this->payments->approve($payment, $request->user(), $request->validated('review_note')));
    }

    public function reject(ReviewPluginPaymentRequest $request, PluginPaymentTransaction $payment)
    {
        return new PluginPaymentTransactionResource($this->payments->reject($payment, $request->user(), $request->validated('review_note')));
    }
}
