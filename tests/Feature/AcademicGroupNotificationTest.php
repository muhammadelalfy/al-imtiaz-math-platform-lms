<?php

namespace Tests\Feature;

use App\Models\AcademicGroup;
use App\Models\AuthorizationPermission;
use App\Models\NotificationChannelSetting;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\User;
use App\Jobs\DispatchNotificationCampaign;
use App\Services\NotificationChannelDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademicGroupNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_authorized_teacher_can_manage_group_members_in_bulk_without_exposing_other_roles(): void
    {
        $teacher = $this->teacherWithPermissions(['groups.manage']);
        $first = Student::factory()->create(['grade' => 'الأول الإعدادي']);
        $second = Student::factory()->create(['grade' => 'الأول الإعدادي']);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $group = $this->postJson('/api/staff/academic-groups', [
            'grade' => 'الأول الإعدادي',
            'name' => 'مجموعة المتفوقين',
        ])->assertCreated()->json();

        $this->putJson("/api/staff/academic-groups/{$group['id']}/students", [
            'student_ids' => [$first->id, $second->id],
        ])->assertOk()
            ->assertJsonPath('students_count', 2)
            ->assertJsonCount(2, 'students');

        $this->getJson("/api/staff/academic-groups/{$group['id']}")
            ->assertOk()
            ->assertJsonPath('students.0.id', $first->id);

        $this->assertDatabaseHas('academic_group_student', ['academic_group_id' => $group['id'], 'student_id' => $second->id]);
    }

    public function test_group_targeted_campaign_queues_only_parent_and_student_accounts_for_group_members(): void
    {
        $teacher = $this->teacherWithPermissions(['notifications.send']);
        $group = AcademicGroup::factory()->create(['grade' => 'الثاني الإعدادي']);
        $member = Student::factory()->create(['grade' => 'الثاني الإعدادي']);
        $outside = Student::factory()->create(['grade' => 'الثاني الإعدادي']);
        $group->students()->sync([$member->id]);

        $parent = User::factory()->create(['role' => 'parent']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $outsideParent = User::factory()->create(['role' => 'parent']);
        StudentAccount::query()->create(['user_id' => $parent->id, 'student_id' => $member->id, 'relationship' => 'parent']);
        StudentAccount::query()->create(['user_id' => $studentUser->id, 'student_id' => $member->id, 'relationship' => 'student']);
        StudentAccount::query()->create(['user_id' => $outsideParent->id, 'student_id' => $outside->id, 'relationship' => 'parent']);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $campaign = $this->postJson('/api/staff/notifications', [
            'audience' => 'academic_group',
            'academic_group_id' => $group->id,
            'title' => 'موعد مراجعة',
            'body' => 'يرجى الحضور قبل الموعد بعشر دقائق.',
            'channels' => ['in_app'],
        ])->assertCreated()
            ->assertJsonPath('recipient_count', 2)
            ->assertJsonPath('academic_group_id', $group->id)
            ->json();

        $this->assertDatabaseHas('notification_deliveries', ['notification_campaign_id' => $campaign['id'], 'recipient_id' => $parent->id]);
        $this->assertDatabaseHas('notification_deliveries', ['notification_campaign_id' => $campaign['id'], 'recipient_id' => $studentUser->id]);
        $this->assertDatabaseMissing('notification_deliveries', ['notification_campaign_id' => $campaign['id'], 'recipient_id' => $outsideParent->id]);

        (new DispatchNotificationCampaign($campaign['id']))->handle(app(NotificationChannelDeliveryService::class));

        $this->assertDatabaseHas('notification_delivery_channels', ['channel' => 'in_app', 'status' => 'sent']);
        $this->assertDatabaseHas('notification_deliveries', ['notification_campaign_id' => $campaign['id'], 'recipient_id' => $parent->id, 'status' => 'delivered']);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_teacher_can_manage_dynamic_channel_options_but_sees_no_provider_secrets(): void
    {
        $teacher = $this->teacherWithPermissions(['notifications.channels.manage']);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $channels = $this->getJson('/api/staff/notification-channels')
            ->assertOk()
            ->assertJsonCount(3)
            ->json();
        $whatsapp = collect($channels)->firstWhere('code', 'whatsapp');

        $this->putJson("/api/staff/notification-channels/{$whatsapp['id']}", [
            'is_enabled' => true,
            'settings' => ['sender_label' => 'الامتياز', 'template_name' => 'class_update'],
        ])->assertOk()
            ->assertJsonPath('settings.template_name', 'class_update')
            ->assertJsonMissing(['access_token'])
            ->assertJsonMissing(['auth_token']);

        $this->assertDatabaseHas('notification_channel_settings', ['code' => 'whatsapp', 'is_enabled' => 1]);
    }

    /** @param array<int, string> $permissions */
    private function teacherWithPermissions(array $permissions): User
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        foreach ($permissions as $name) {
            $teacher->givePermissionTo(AuthorizationPermission::query()->create([
                'name' => $name,
                'guard_name' => 'web',
                'label' => $name,
                'is_system' => true,
            ]));
        }

        return $teacher;
    }
}
