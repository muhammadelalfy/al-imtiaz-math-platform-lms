<?php

namespace App\Contracts\Repositories;

use App\Models\TeacherSlackLogDestination;
use App\Models\User;

interface TeacherSlackLogDestinationRepositoryInterface
{
    public function findFor(User $teacher): ?TeacherSlackLogDestination;

    public function findForUpdate(User $teacher): ?TeacherSlackLogDestination;

    public function save(TeacherSlackLogDestination $destination): TeacherSlackLogDestination;

    public function deleteFor(User $teacher): void;
}
