<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_templates', function (Blueprint $table) {
            $table->string('print_header')->nullable()->after('watermark_opacity');
            $table->string('print_footer')->nullable()->after('print_header');
        });
    }

    public function down(): void
    {
        Schema::table('exam_templates', function (Blueprint $table) {
            $table->dropColumn(['print_header', 'print_footer']);
        });
    }
};
