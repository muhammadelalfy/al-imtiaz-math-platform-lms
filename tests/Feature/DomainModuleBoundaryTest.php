<?php

namespace Tests\Feature;

use Modules\Auth\Contracts\AuthenticationBoundary;
use Modules\Exams\Contracts\ExamBoundary;
use Modules\Platform\Contracts\PlatformControlPlaneBoundary;
use Modules\Settings\Contracts\SettingsBoundary;
use Modules\Teacher\Contracts\TeacherOnboardingBoundary;
use Modules\Ui\Contracts\UiBoundary;
use Tests\TestCase;

class DomainModuleBoundaryTest extends TestCase
{
    public function test_enabled_domain_modules_expose_their_local_boundary_contracts(): void
    {
        /** @var array<string, bool> $statuses */
        $statuses = json_decode((string) file_get_contents(base_path('modules_statuses.json')), true, 512, JSON_THROW_ON_ERROR);

        $boundaries = [
            'Auth' => AuthenticationBoundary::class,
            'Teacher' => TeacherOnboardingBoundary::class,
            'Settings' => SettingsBoundary::class,
            'Exams' => ExamBoundary::class,
            'Ui' => UiBoundary::class,
            'Platform' => PlatformControlPlaneBoundary::class,
        ];

        foreach ($boundaries as $module => $contract) {
            $this->assertTrue($statuses[$module] ?? false);
            $descriptor = app($contract)->descriptor();
            $this->assertSame($module, $descriptor['module']);
            $this->assertNotEmpty($descriptor['owns']);
            $this->assertStringStartsWith('local-', $descriptor['transport']);
        }
    }
}
