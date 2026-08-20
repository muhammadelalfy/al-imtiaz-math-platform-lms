<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\WorksheetRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignWorksheetRequest;
use App\Http\Requests\StoreWorksheetRequest;
use App\Http\Requests\SubmitWorksheetRequest;
use App\Http\Resources\WorksheetAssignmentResource;
use App\Http\Resources\WorksheetResource;
use App\Models\Worksheet;
use App\Models\WorksheetAssignment;
use Illuminate\Http\Request;

class WorksheetController extends Controller
{
    public function __construct(private readonly WorksheetRepositoryInterface $worksheets)
    {
    }

    public function index(Request $request)
    {
        return WorksheetResource::collection($this->worksheets->paginateFor($request->user()));
    }

    public function store(StoreWorksheetRequest $request)
    {
        $worksheet = $this->worksheets->create($request->validated(), $request->user());

        return (new WorksheetResource($worksheet))->response()->setStatusCode(201);
    }

    public function show(Request $request, Worksheet $worksheet)
    {
        return new WorksheetResource($this->worksheets->findVisibleFor($request->user(), $worksheet));
    }

    public function assign(AssignWorksheetRequest $request, Worksheet $worksheet)
    {
        return new WorksheetResource($this->worksheets->assign($worksheet, $request->validated('student_ids')));
    }

    public function submit(SubmitWorksheetRequest $request, WorksheetAssignment $assignment)
    {
        return new WorksheetAssignmentResource(
            $this->worksheets->submit($request->user(), $assignment, $request->validated()),
        );
    }
}
