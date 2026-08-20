<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plugin_products')->updateOrInsert(
            ['slug' => 'payment-center'],
            [
                'name' => 'مركز المدفوعات والاشتراكات',
                'description' => 'إدارة اشتراكات الطلاب، التحصيل، والتحويلات اليدوية عبر فودافون كاش وإنستاباي وفوري.',
                'version' => '1.0.0',
                'module_name' => 'CorePayments',
                'artifact_path' => null,
                'artifact_sha256' => null,
                'price' => 0,
                'is_active' => true,
                'metadata' => json_encode([
                    'category' => 'payments',
                    'language' => 'ar',
                    'core_feature' => true,
                    'available_roles' => ['admin', 'teacher'],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('plugin_products')->where('slug', 'payment-center')->delete();
    }
};
