<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TeacherAcademyIdentityService
{
    public function currentFor(User $teacher): Tenant
    {
        if ($teacher->role !== 'teacher' || $teacher->tenant_id === null) {
            throw new AuthorizationException('هذه الإعدادات متاحة للمعلم المالك فقط.');
        }

        return Tenant::query()->findOrFail($teacher->tenant_id);
    }

    public function rename(User $teacher, string $academyName): Tenant
    {
        if ($teacher->role !== 'teacher' || $teacher->tenant_id === null) {
            throw new AuthorizationException('هذه الإعدادات متاحة للمعلم المالك فقط.');
        }

        return DB::transaction(function () use ($teacher, $academyName): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()
                ->whereKey($teacher->tenant_id)
                ->lockForUpdate()
                ->firstOr(function () {
                    throw (new ModelNotFoundException())->setModel(Tenant::class);
                });

            $tenant->update(['name' => $academyName]);

            return $tenant->fresh() ?? $tenant;
        }, attempts: 3);
    }
}
