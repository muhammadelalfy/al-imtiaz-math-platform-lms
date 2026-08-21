<?php

namespace Tests\Feature;

use App\Models\TeacherDashboardLayout;
use App\Models\User;
use App\Services\TeacherDashboardLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_receives_default_order_and_can_persist_a_personal_card_order(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->getJson('/api/teacher/dashboard-layout')
            ->assertOk()
            ->assertJsonPath('card_order', TeacherDashboardLayoutService::DEFAULT_CARD_ORDER);

        $order = ['payments', 'learning_flow', 'attendance', 'exam_performance'];
        $this->putJson('/api/teacher/dashboard-layout', ['card_order' => $order])
            ->assertOk()
            ->assertJsonPath('card_order', $order);

        $this->assertDatabaseHas('teacher_dashboard_layouts', ['user_id' => $teacher->id]);
        $this->assertSame($order, TeacherDashboardLayout::query()->where('user_id', $teacher->id)->firstOrFail()->card_order);
    }

    public function test_teacher_can_reset_to_the_default_order_and_invalid_or_non_teacher_requests_are_rejected(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        TeacherDashboardLayout::factory()->create([
            'user_id' => $teacher->id,
            'card_order' => ['payments', 'learning_flow', 'attendance', 'exam_performance'],
        ]);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->putJson('/api/teacher/dashboard-layout', ['card_order' => ['attendance']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('card_order');

        $this->deleteJson('/api/teacher/dashboard-layout')->assertNoContent();
        $this->assertDatabaseMissing('teacher_dashboard_layouts', ['user_id' => $teacher->id]);
        $this->getJson('/api/teacher/dashboard-layout')
            ->assertOk()
            ->assertJsonPath('card_order', TeacherDashboardLayoutService::DEFAULT_CARD_ORDER);

        $parent = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($parent, ['guard:parent']);
        $this->getJson('/api/teacher/dashboard-layout')->assertForbidden();
    }
}
