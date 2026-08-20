<?php

namespace App\Repositories;

use App\Contracts\Repositories\TeacherSlackLogDestinationRepositoryInterface;
use App\Models\TeacherSlackLogDestination;
use App\Models\User;

class EloquentTeacherSlackLogDestinationRepository implements TeacherSlackLogDestinationRepositoryInterface
{
    public function findFor(User $teacher): ?TeacherSlackLogDestination
    {
        return TeacherSlackLogDestination::query()
            ->where('user_id', $teacher->id)
            ->first();
    }

    public function findForUpdate(User $teacher): ?TeacherSlackLogDestination
    {
        return TeacherSlackLogDestination::query()
            ->where('user_id', $teacher->id)
            ->lockForUpdate()
            ->first();
    }

    public function save(TeacherSlackLogDestination $destination): TeacherSlackLogDestination
    {
        $destination->save();

        return $destination->fresh();
    }

    public function deleteFor(User $teacher): void
    {
        TeacherSlackLogDestination::query()
            ->where('user_id', $teacher->id)
            ->lockForUpdate()
            ->delete();
    }
}
