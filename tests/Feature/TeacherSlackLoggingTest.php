<?php

namespace Tests\Feature;

use App\Contracts\Repositories\TeacherSlackLogDestinationRepositoryInterface;
use App\Models\User;
use App\Repositories\EloquentTeacherSlackLogDestinationRepository;
use App\Services\TeacherSlackLogDestinationService;
use App\Services\TeacherSlackRequestLogDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TeacherSlackLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_slack_destination_repository_contract_is_bound_to_the_eloquent_persistence_implementation(): void
    {
        $this->assertInstanceOf(
            EloquentTeacherSlackLogDestinationRepository::class,
            $this->app->make(TeacherSlackLogDestinationRepositoryInterface::class)
        );
    }

    public function test_teacher_can_configure_an_encrypted_slack_destination_without_receiving_the_secret_back(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->putJson('/api/teacher/slack-log-destination', [
            'channel_label' => 'سجل أستاذ أحمد',
            'webhook_url' => 'https://hooks.slack.com/services/T123/B456/secret-token',
            'is_enabled' => true,
        ])->assertOk()
            ->assertJsonPath('channel_label', 'سجل أستاذ أحمد')
            ->assertJsonPath('configured', true)
            ->assertJsonMissing(['webhook_url']);

        $this->assertDatabaseHas('teacher_slack_log_destinations', [
            'user_id' => $teacher->id,
            'channel_label' => 'سجل أستاذ أحمد',
            'is_enabled' => true,
        ]);
        $this->assertNotSame(
            'https://hooks.slack.com/services/T123/B456/secret-token',
            (string) $this->app->make(TeacherSlackLogDestinationService::class)->findFor($teacher)?->getRawOriginal('webhook_url')
        );
    }

    public function test_only_a_teacher_can_manage_their_slack_destination_and_only_slack_hosts_are_accepted(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($parent, ['guard:parent']);
        $this->getJson('/api/teacher/slack-log-destination')->assertForbidden();

        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher, ['guard:teacher']);
        $this->putJson('/api/teacher/slack-log-destination', [
            'channel_label' => 'غير صالح',
            'webhook_url' => 'https://example.test/logs',
            'is_enabled' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('webhook_url');
    }

    public function test_teacher_can_transactionally_remove_their_saved_destination(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->app->make(TeacherSlackLogDestinationService::class)->update($teacher, [
            'channel_label' => 'سجل العمليات',
            'webhook_url' => 'https://hooks.slack.com/services/T123/B456/secret-token',
            'is_enabled' => true,
        ]);
        Sanctum::actingAs($teacher, ['guard:teacher']);

        $this->deleteJson('/api/teacher/slack-log-destination')->assertNoContent();

        $this->assertDatabaseMissing('teacher_slack_log_destinations', ['user_id' => $teacher->id]);
    }

    public function test_enabled_teacher_destination_receives_a_redacted_operation_log_without_storing_log_payloads(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $destinations = $this->app->make(TeacherSlackLogDestinationService::class);
        $destinations->update($teacher, [
            'channel_label' => 'سجل العمليات',
            'webhook_url' => 'https://hooks.slack.com/services/T123/B456/secret-token',
            'is_enabled' => true,
        ]);
        Http::fake();

        $this->app->make(TeacherSlackRequestLogDispatcher::class)->dispatch(
            $teacher,
            Request::create('/api/students/41', 'POST'),
            new Response('', 201),
            24
        );

        Http::assertSent(function ($request): bool {
            $text = (string) ($request->data()['text'] ?? '');

            return $request->url() === 'https://hooks.slack.com/services/T123/B456/secret-token'
                && str_contains($text, 'POST /api/students/:id')
                && str_contains($text, 'HTTP 201')
                && !str_contains($text, 'secret-token');
        });
        $this->assertDatabaseCount('teacher_slack_log_destinations', 1);
    }
}
