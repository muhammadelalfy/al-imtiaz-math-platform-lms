<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTeacherDashboardLayoutRequest;
use App\Http\Resources\TeacherDashboardLayoutResource;
use App\Models\TeacherDashboardLayout;
use App\Models\User;
use App\Services\TeacherDashboardLayoutService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherDashboardLayoutController extends Controller
{
    public function __construct(private readonly TeacherDashboardLayoutService $layouts)
    {
    }

    public function show(Request $request): TeacherDashboardLayoutResource
    {
        /** @var User $teacher */
        $teacher = $request->user();

        return new TeacherDashboardLayoutResource(new TeacherDashboardLayout([
            'card_order' => $this->layouts->for($teacher),
        ]));
    }

    public function update(UpdateTeacherDashboardLayoutRequest $request): TeacherDashboardLayoutResource
    {
        /** @var User $teacher */
        $teacher = $request->user();

        return new TeacherDashboardLayoutResource(
            $this->layouts->save($teacher, $request->validated('card_order'))
        );
    }

    public function destroy(Request $request): Response
    {
        /** @var User $teacher */
        $teacher = $request->user();
        $this->layouts->reset($teacher);

        return response()->noContent();
    }
}
