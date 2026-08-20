<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 140);
            $table->string('slug', 80)->unique();
            $table->string('login_domain', 190)->nullable()->unique();
            $table->string('domain_status', 20)->default('pending')->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete()->index();
            $table->boolean('is_super_admin')->default(false)->after('role')->index();
        });

        Schema::create('subscription_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('tagline', 180)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->unsignedSmallInteger('teacher_limit')->default(1);
            $table->unsignedInteger('student_limit')->default(100);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_package_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reminder_shown_at')->nullable();
            $table->string('payment_reference', 160)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_packages');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('is_super_admin');
        });
        Schema::dropIfExists('tenants');
    }
};
