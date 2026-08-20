<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('label', 120);
            $table->string('recipient', 255)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('plugin_payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_product_id')->constrained('plugin_products')->cascadeOnDelete();
            $table->foreignId('plugin_payment_method_id')->constrained('plugin_payment_methods')->restrictOnDelete();
            $table->string('status', 32)->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('reference', 160)->nullable()->unique();
            $table->string('customer_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['plugin_product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_payment_transactions');
        Schema::dropIfExists('plugin_payment_methods');
    }
};
