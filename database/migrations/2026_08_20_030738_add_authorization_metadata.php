<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', static function (Blueprint $table): void {
            $table->string('label')->nullable()->after('name');
            $table->text('description')->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->index()->after('description');
        });

        Schema::table('roles', static function (Blueprint $table): void {
            $table->string('label')->nullable()->after('name');
            $table->text('description')->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->index()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropIndex(['is_system']);
            $table->dropColumn(['label', 'description', 'is_system']);
        });

        Schema::table('permissions', static function (Blueprint $table): void {
            $table->dropIndex(['is_system']);
            $table->dropColumn(['label', 'description', 'is_system']);
        });
    }
};
