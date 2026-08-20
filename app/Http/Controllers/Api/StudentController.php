<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\DashboardMetricsCacheInterface;
use App\Contracts\Repositories\StudentRepositoryInterface;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentRepositoryInterface $students,
        private readonly DashboardMetricsCacheInterface $metricsCache,
    ) {
    }

    public function index(Request $request)
    {
        return StudentResource::collection($this->students->paginateFor(
            $request->user(),
            $request->only(['grade', 'group', 'search']),
        ));
    }

    public function store(StoreStudentRequest $request)
    {
        $student = $this->students->create($request->validated());
        $this->metricsCache->forget();

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($request, $student);

        return new StudentResource($this->students->findDetailed($student));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student = $this->students->update($student, $request->validated());
        $this->metricsCache->forget();

        return new StudentResource($student);
    }

    public function destroy(Request $request, Student $student)
    {
        abort_unless($request->user()->isAnyRole('admin'), 403);
        $this->students->delete($student);
        $this->metricsCache->forget();

        return response()->noContent();
    }

    public function qr(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($request, $student);
        abort_unless($request->user()->isAnyRole('admin', 'teacher', 'parent', 'student'), 403);

        return ['student_id' => $student->id, 'payload' => $student->ensureQrToken(), 'generated_at' => now()->toISOString()];
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        if ($request->user()->isAnyRole('student', 'parent')) {
            $account = $request->user()->loadMissing('studentAccount')->studentAccount;
            abort_unless($account && $account->student_id === $student->id, 403);
        }
    }
}
