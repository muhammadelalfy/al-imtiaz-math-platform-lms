<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TeacherSlackLogDestinationStorageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTeacherSlackLogDestinationRequest;
use App\Http\Resources\TeacherSlackLogDestinationResource;
use App\Models\TeacherSlackLogDestination;
use App\Services\TeacherSlackLogDestinationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeacherSlackLogDestinationController extends Controller
{
    public function __construct(private readonly TeacherSlackLogDestinationService $destinations)
    {
    }

    public function show(Request $request): TeacherSlackLogDestinationResource
    {
        $destination = $this->destinations->findFor($request->user());

        return new TeacherSlackLogDestinationResource($destination ?? new TeacherSlackLogDestination([
            'is_enabled' => false,
        ]));
    }

    public function update(UpdateTeacherSlackLogDestinationRequest $request): TeacherSlackLogDestinationResource|JsonResponse
    {
        try {
            return new TeacherSlackLogDestinationResource(
                $this->destinations->update($request->user(), $request->validated())
            );
        } catch (TeacherSlackLogDestinationStorageException) {
            return response()->json([
                'message' => 'تعذر حفظ إعداد Slack حالياً. لم يتم تعديل إعداداتك.',
            ], 503);
        }
    }

    public function destroy(Request $request): Response
    {
        abort_unless($request->user()?->isAnyRole('teacher'), 403);
        try {
            $this->destinations->clear($request->user());
        } catch (TeacherSlackLogDestinationStorageException) {
            return response()->json([
                'message' => 'تعذر حذف إعداد Slack حالياً. لم يتم تغيير إعداداتك.',
            ], 503);
        }

        return response()->noContent();
    }
}
