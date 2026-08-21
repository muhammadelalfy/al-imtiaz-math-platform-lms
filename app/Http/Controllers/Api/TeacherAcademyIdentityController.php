<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTeacherAcademyIdentityRequest;
use App\Http\Resources\TeacherAcademyIdentityResource;
use App\Models\User;
use App\Services\TeacherAcademyIdentityService;
use Illuminate\Http\Request;

class TeacherAcademyIdentityController extends Controller
{
    public function __construct(private readonly TeacherAcademyIdentityService $academyIdentity)
    {
    }

    public function show(Request $request): TeacherAcademyIdentityResource
    {
        /** @var User $teacher */
        $teacher = $request->user();

        return new TeacherAcademyIdentityResource($this->academyIdentity->currentFor($teacher));
    }

    public function update(UpdateTeacherAcademyIdentityRequest $request): TeacherAcademyIdentityResource
    {
        /** @var User $teacher */
        $teacher = $request->user();

        return new TeacherAcademyIdentityResource(
            $this->academyIdentity->rename($teacher, $request->validated('academy_name'))
        );
    }
}
