<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\AcademicGroupRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicGroupRequest;
use App\Http\Requests\SyncAcademicGroupStudentsRequest;
use App\Http\Requests\UpdateAcademicGroupRequest;
use App\Http\Resources\AcademicGroupResource;
use App\Models\AcademicGroup;
use Illuminate\Http\Request;

class AcademicGroupController extends Controller
{
    public function __construct(private readonly AcademicGroupRepositoryInterface $groups)
    {
    }

    public function index()
    {
        return AcademicGroupResource::collection($this->groups->all());
    }

    public function show(AcademicGroup $academicGroup)
    {
        return new AcademicGroupResource($academicGroup->load([
            'students' => fn ($query) => $query->select(['students.id', 'students.name', 'students.grade', 'students.group']),
        ])->loadCount('students'));
    }

    public function store(StoreAcademicGroupRequest $request)
    {
        return (new AcademicGroupResource($this->groups->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateAcademicGroupRequest $request, AcademicGroup $academicGroup)
    {
        return new AcademicGroupResource($this->groups->update($academicGroup, $request->validated()));
    }

    public function destroy(Request $request, AcademicGroup $academicGroup)
    {
        abort_unless($request->user()?->can('groups.manage'), 403);
        $this->groups->delete($academicGroup);

        return response()->noContent();
    }

    public function syncStudents(SyncAcademicGroupStudentsRequest $request, AcademicGroup $academicGroup)
    {
        return new AcademicGroupResource($this->groups->syncStudents($academicGroup, $request->validated('student_ids')));
    }
}
