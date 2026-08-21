<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('database_schema', 80)->nullable()->unique();
            $table->string('schema_status', 24)->default('pending')->index();
            $table->string('schema_version', 120)->nullable();
            $table->timestamp('schema_provisioned_at')->nullable();
            $table->text('provisioning_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['database_schema']);
            $table->dropColumn(['database_schema', 'schema_status', 'schema_version', 'schema_provisioned_at', 'provisioning_error']);
        });
    }
};
