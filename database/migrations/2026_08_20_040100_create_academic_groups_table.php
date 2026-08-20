<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('grade', 100);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['grade', 'name']);
            $table->index(['grade', 'is_active']);
        });

        Schema::create('academic_group_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('student_id');
            $table->timestamps();
            $table->unique(['academic_group_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::table('notification_campaigns', function (Blueprint $table): void {
            $table->unsignedBigInteger('academic_group_id')->nullable()->after('grade')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notification_campaigns', function (Blueprint $table): void {
            $table->dropIndex(['academic_group_id']);
            $table->dropColumn('academic_group_id');
        });
        Schema::dropIfExists('academic_group_student');
        Schema::dropIfExists('academic_groups');
    }
};
