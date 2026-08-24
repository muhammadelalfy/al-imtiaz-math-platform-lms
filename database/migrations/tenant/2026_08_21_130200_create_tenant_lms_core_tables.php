<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('group');
            $table->string('grade');
            $table->string('phone');
            $table->string('parent_phone')->nullable();
            $table->enum('status', ['excellent', 'average', 'weak'])->default('average');
            $table->timestamps();
        });

        Schema::create('worksheets', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('subject');
            $table->string('grade');
            $table->text('instructions')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('worksheet_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('worksheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['assigned', 'in_progress', 'submitted', 'graded'])->default('assigned');
            $table->dateTime('assigned_at');
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('max_score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['worksheet_id', 'student_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date_at');
            $table->enum('status', ['present', 'absent', 'late']);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
            $table->unique(['student_id', 'date_at']);
        });

        Schema::create('exam_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('score');
            $table->unsignedInteger('max_score');
            $table->dateTime('taken_at');
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->date('due_at');
            $table->date('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('worksheet_assignments');
        Schema::dropIfExists('worksheets');
        Schema::dropIfExists('students');
    }
};
