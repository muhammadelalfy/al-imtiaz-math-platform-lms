<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table): void {
            $table->id();
            // The managed production database predates the Laravel migration
            // and uses an integer users.id, while a clean Laravel install uses
            // an unsigned big integer. Keep this transitional reference
            // compatible with both; authorization and recipient resolution
            // enforce ownership in the application service.
            $table->unsignedInteger('sent_by')->index();
            $table->string('audience', 32);
            $table->string('grade')->nullable()->index();
            $table->string('title', 120);
            $table->text('body');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['sent_by', 'created_at']);
            $table->index(['audience', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('recipient_id');
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_campaign_id', 'recipient_id'], 'notification_delivery_unique_recipient');
            $table->index(['status', 'created_at']);
            $table->index(['recipient_id', 'status']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_campaigns');
    }
};
