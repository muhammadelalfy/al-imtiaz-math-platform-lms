<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channel_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label', 80);
            $table->boolean('is_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('notification_delivery_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_delivery_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('status', 20)->default('pending');
            $table->string('provider_message_id', 160)->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_delivery_id', 'channel']);
            $table->index(['channel', 'status']);
        });

        Schema::table('notification_campaigns', function (Blueprint $table): void {
            $table->json('channels')->nullable()->after('academic_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table): void {
            $table->dropColumn('channels');
        });
        Schema::dropIfExists('notification_delivery_channels');
        Schema::dropIfExists('notification_channel_settings');
    }
};
