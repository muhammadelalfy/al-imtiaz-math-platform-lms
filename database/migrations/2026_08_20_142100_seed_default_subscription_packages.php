<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            [
                'code' => 'starter', 'name' => 'البداية', 'tagline' => 'للمعلم الذي يبدأ مركزه الرقمي',
                'description' => 'إدارة مركز واحد، حضور ذكي، شيتات، اختبارات، وتقارير أساسية.',
                'price_cents' => 49000, 'duration_days' => 30, 'teacher_limit' => 1, 'student_limit' => 100,
                'features' => ['الحضور QR', 'الشيتات والاختبارات', 'تقارير أساسية'], 'sort_order' => 10,
            ],
            [
                'code' => 'growth', 'name' => 'النمو', 'tagline' => 'للأكاديميات التي تتوسع بثقة',
                'description' => 'يشمل المجموعات والإشعارات والتقارير المتقدمة وطلاباً أكثر.',
                'price_cents' => 99000, 'duration_days' => 30, 'teacher_limit' => 5, 'student_limit' => 500,
                'features' => ['كل مزايا البداية', 'المجموعات والإشعارات', 'تقارير متقدمة'], 'sort_order' => 20,
            ],
            [
                'code' => 'scale', 'name' => 'التميز', 'tagline' => 'للمراكز متعددة المعلمين والطلاب',
                'description' => 'سعة أكبر، إعدادات تشغيل متقدمة، وإدارة مركز موسعة.',
                'price_cents' => 179000, 'duration_days' => 30, 'teacher_limit' => 20, 'student_limit' => 2000,
                'features' => ['كل مزايا النمو', 'سعة موسعة', 'إدارة تشغيل متقدمة'], 'sort_order' => 30,
            ],
        ] as $package) {
            DB::table('subscription_packages')->updateOrInsert(['code' => $package['code']], [
                ...$package,
                'currency' => 'EGP', 'features' => json_encode($package['features'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('subscription_packages')->whereIn('code', ['starter', 'growth', 'scale'])->delete();
    }
};
