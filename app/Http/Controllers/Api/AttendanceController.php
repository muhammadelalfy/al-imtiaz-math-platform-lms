<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesStaff;
use App\Http\Requests\ScanAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Modules\Attendance\Services\AttendanceDomainService;

class AttendanceController extends Controller
{
    use AuthorizesStaff;

    public function __construct(private readonly AttendanceDomainService $attendance)
    {
    }

    public function index(Request $request)
    {
        $query = $this->attendance->query();
        $this->scope($query, $request);

        return AttendanceResource::collection($query->paginate(50));
    }

    public function store(StoreAttendanceRequest $request)
    {
        return (new AttendanceResource($this->attendance->create($request->validated(), $request->user()->id)))
            ->response()
            ->setStatusCode(201);
    }

    public function scan(ScanAttendanceRequest $request)
    {
        $payload = $request->validated('payload');
        $result = $this->attendance->scan($payload, $request->user()->id);

        return response()->json([
            'already_recorded' => $result['already_recorded'],
            'attendance' => (new AttendanceResource($result['attendance']))->resolve($request),
        ], $result['already_recorded'] ? 200 : 201);
    }

    public function update(UpdateAttendanceRequest $request, AttendanceRecord $attendance)
    {
        return new AttendanceResource($this->attendance->update($attendance, $request->validated()));
    }

    public function destroy(Request $request, AttendanceRecord $attendance)
    {
        $this->authorizeStaff($request);
        $this->attendance->delete($attendance);

        return response()->noContent();
    }

    private function scope($query, Request $request): void
    {
        $account = $request->user()->loadMissing('studentAccount')->studentAccount;
        if ($request->user()->isAnyRole('student', 'parent')) {
            abort_unless($account !== null, 403);
            $query->where('student_id', $account->student_id);
        }
    }
}
