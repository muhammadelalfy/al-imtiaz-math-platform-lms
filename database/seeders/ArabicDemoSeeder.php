<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\ExamDepartment;
use App\Models\ExamQuestion;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Models\ExamSessionEvent;
use App\Models\ExamTemplate;
use App\Models\OfflineSyncOperation;
use App\Models\Payment;
use App\Models\QuestionBankQuestion;
use App\Models\PluginProduct;
use App\Models\PluginPurchase;
use App\Models\AcademicGroup;
use App\Models\AuthorizationPermission;
use App\Models\AuthorizationRole;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TeacherDashboardLayout;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Worksheet;
use App\Models\WorksheetAssignment;
use App\Services\PostgresTenantSchemaProvisioner;
use Database\Factories\StudentFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ArabicDemoSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@local.test';
    private const ADMIN_PASSWORD = 'AdminLocal!2026';
    private const TEACHER_EMAIL = 'teacher@local.test';
    private const TEACHER_PASSWORD = 'TeacherLocal!2026';
    private const PARENT_PASSWORD = 'ParentLocal!2026';
    private const STUDENT_PASSWORD = 'StudentLocal!2026';
    private const LEVEL_STUDENT_COUNT = 5000;
    private const LEVEL_STUDENT_UPSERT_CHUNK_SIZE = 500;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('ArabicDemoSeeder is restricted to local and testing environments.');
        }

        $admin = $this->user(self::ADMIN_EMAIL, 'مدير الامتياز', 'admin', self::ADMIN_PASSWORD);
        $admin->forceFill(['is_super_admin' => true])->save();
        $teacher = $this->user(self::TEACHER_EMAIL, 'أستاذ الرياضيات', 'teacher', self::TEACHER_PASSWORD);
        $tenant = $this->seedSubscriptionPlatform($teacher);
        $this->seedAuthorization($teacher);
        $students = $this->seedStudents();
        $this->seedAcademicGroups($students);
        $this->seedExams($teacher, $students);
        $this->seedQuestionBank($teacher);
        $this->seedPlugins($admin);

        $worksheets = collect([
            ['title' => 'مراجعة المعادلات الخطية', 'grade' => 'الأول الإعدادي'],
            ['title' => 'تدريب على النسب والتناسب', 'grade' => 'الثاني الإعدادي'],
            ['title' => 'ورقة الهندسة والمثلثات', 'grade' => 'الثالث الإعدادي'],
        ])->map(fn (array $attributes) => $this->worksheet($attributes, $teacher))->all();

        foreach (array_slice($students, 0, 6) as $index => $student) {
            $parent = $this->user("parent{$index}@local.test", "ولي أمر {$student->name}", 'parent', self::PARENT_PASSWORD);
            $learner = $this->user("student{$index}@local.test", $student->name, 'student', self::STUDENT_PASSWORD);
            $parent->forceFill(['tenant_id' => $tenant->id])->save();
            $learner->forceFill(['tenant_id' => $tenant->id])->save();
            StudentAccount::updateOrCreate(['user_id' => $parent->id], ['student_id' => $student->id, 'relationship' => 'parent']);
            StudentAccount::updateOrCreate(['user_id' => $learner->id], ['student_id' => $student->id, 'relationship' => 'student']);

            $worksheet = $worksheets[$index % count($worksheets)];
            WorksheetAssignment::updateOrCreate(
                ['worksheet_id' => $worksheet->id, 'student_id' => $student->id],
                ['status' => $index % 3 === 0 ? 'graded' : 'submitted', 'assigned_at' => now()->subDays(7), 'submitted_at' => now()->subDays(2), 'score' => 14 + ($index % 7), 'max_score' => 20, 'feedback' => 'عمل جيد، راجع خطوات الحل بهدوء.'],
            );

            for ($day = 1; $day <= 5; $day++) {
                $date = now()->subDays($day);
                AttendanceRecord::updateOrCreate(
                    ['student_id' => $student->id, 'attendance_date' => $date->toDateString()],
                    ['date_at' => $date->setTime(8, 15), 'status' => $day === 3 && $index % 3 === 0 ? 'late' : 'present', 'note' => null, 'recorded_by' => $teacher->id],
                );
            }

            ExamResult::updateOrCreate(
                ['student_id' => $student->id, 'title' => 'اختبار منتصف الفصل'],
                ['score' => 12 + ($index % 9), 'max_score' => 20, 'taken_at' => now()->subDays(10), 'recorded_by' => $teacher->id],
            );

            Payment::updateOrCreate(
                ['student_id' => $student->id, 'due_at' => now()->startOfMonth()],
                ['amount' => 450, 'status' => $index % 4 === 0 ? 'pending' : 'paid', 'paid_at' => $index % 4 === 0 ? null : now()->subDays(4), 'note' => 'اشتراك شهر تجريبي', 'recorded_by' => $admin->id],
            );
        }

        OfflineSyncOperation::query()->updateOrCreate(
            ['user_id' => $teacher->id, 'client_operation_id' => 'f5115d94-31d5-4bc3-a14c-74e23c4ed0ad'],
            [
                'type' => 'attendance.create',
                'status' => 'applied',
                'payload' => ['student_id' => $students[0]->id, 'status' => 'present'],
                'result' => ['domain' => 'attendance', 'record_id' => 1],
                'occurred_at' => now()->subDay(),
                'processed_at' => now()->subDay(),
            ],
        );

        $this->command?->info('Arabic LMS demo data is ready. QR codes were generated for all demo students.');
        $this->command?->info('Level CRUD demo set: '.self::LEVEL_STUDENT_COUNT.' Arabic students across seven grades and three groups per grade.');
        $this->command?->info('Admin: '.self::ADMIN_EMAIL.' / '.self::ADMIN_PASSWORD);
        $this->command?->info('Teacher: '.self::TEACHER_EMAIL.' / '.self::TEACHER_PASSWORD);
        $this->command?->info('Parent password: '.self::PARENT_PASSWORD.' · Student password: '.self::STUDENT_PASSWORD);
    }

    private function seedQuestionBank(User $teacher): void
    {
        $questions = [
            ['title' => 'تبسيط تعبير جبري', 'type' => 'math', 'grade' => 'الأول الإعدادي', 'prompt_html' => '<p>بسّط التعبير الجبري التالي.</p>', 'options' => ['notation' => 'س^2 + 2س + 1'], 'correct_answer' => '(س + 1)^2', 'points' => 3, 'tags' => 'جبر،تبسيط'],
            ['title' => 'مساحة مستطيل', 'type' => 'geometry', 'grade' => 'الأول الإعدادي', 'prompt_html' => '<p>احسب مساحة المستطيل الموضح في الرسم.</p>', 'options' => ['shape' => 'rectangle', 'dimensions' => ['width' => '6 سم', 'height' => '4 سم']], 'correct_answer' => '24 سم²', 'points' => 4, 'tags' => 'هندسة،مساحة'],
            ['title' => 'قيمة تعبير', 'type' => 'mcq', 'grade' => 'الثاني الإعدادي', 'prompt_html' => '<p>ما قيمة ٣ × ٤؟</p>', 'options' => ['١٠', '١٢', '١٤'], 'correct_answer' => '١٢', 'points' => 1, 'tags' => 'حساب'],
        ];
        foreach ($questions as $question) {
            QuestionBankQuestion::updateOrCreate(['title' => $question['title']], [...$question, 'created_by' => $teacher->id, 'is_active' => true]);
        }
    }

    private function seedAuthorization(User $teacher): void
    {
        $systemPermissions = [
            'authorization.manage' => ['إدارة الأدوار والصلاحيات', 'الوصول إلى لوحة إدارة الأدوار والصلاحيات للعاملين.'],
            'students.read' => ['عرض الطلاب', 'عرض سجلات الطلاب ضمن صلاحيات العمل.'],
            'attendance.manage' => ['إدارة الحضور', 'تسجيل الحضور وتحديثه.'],
            'exams.manage' => ['إدارة الاختبارات', 'إعداد الاختبارات والأسئلة ومتابعتها.'],
            'worksheets.manage' => ['إدارة الشيتات', 'إنشاء الشيتات وتعيينها للطلاب.'],
            'reports.read' => ['عرض التقارير', 'عرض مؤشرات وتقارير المركز.'],
            'notifications.send' => ['إرسال الإشعارات', 'إرسال إشعارات داخل المنصة للطلاب وأولياء الأمور ضمن الجمهور المحدد.'],
            'notifications.channels.manage' => ['إدارة قنوات الإشعار', 'إدارة خيارات قنوات الإشعار غير السرية والقوالب المعتمدة دون الوصول إلى مفاتيح مزودي الخدمة.'],
            'groups.manage' => ['إدارة المجموعات الدراسية', 'إنشاء المجموعات الدراسية وتحديد طلابها وإدارتها.'],
        ];

        foreach ($systemPermissions as $name => [$label, $description]) {
            AuthorizationPermission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['label' => $label, 'description' => $description, 'is_system' => true],
            );
        }

        $manager = AuthorizationRole::query()->updateOrCreate(
            ['name' => 'staff-permission-manager', 'guard_name' => 'web'],
            ['label' => 'مسؤول صلاحيات العاملين', 'description' => 'دور نظامي يمنح للمعلم المصرح له إدارة الأدوار والصلاحيات غير النظامية.', 'is_system' => true],
        );
        $manager->syncPermissions(AuthorizationPermission::query()->where('name', 'authorization.manage')->firstOrFail());

        $notifier = AuthorizationRole::query()->updateOrCreate(
            ['name' => 'staff-notification-sender', 'guard_name' => 'web'],
            ['label' => 'مرسل إشعارات العاملين', 'description' => 'دور نظامي يمنح للمعلم المصرح له إرسال الإشعارات الداخلية للطلاب وأولياء الأمور.', 'is_system' => true],
        );
        $notifier->syncPermissions(AuthorizationPermission::query()->whereIn('name', ['notifications.send', 'notifications.channels.manage'])->get());

        $groupManager = AuthorizationRole::query()->updateOrCreate(
            ['name' => 'staff-academic-group-manager', 'guard_name' => 'web'],
            ['label' => 'مسؤول المجموعات الدراسية', 'description' => 'دور نظامي يمنح للمعلم المصرح له إدارة مجموعات الطلاب الدراسية.', 'is_system' => true],
        );
        $groupManager->syncPermissions(AuthorizationPermission::query()->where('name', 'groups.manage')->firstOrFail());
        $teacher->syncRoles([$manager, $notifier, $groupManager]);
    }

    private function seedExams(User $teacher, array $students): void
    {
        $departments = collect([
            ['name' => 'الجبر والمعادلات', 'slug' => 'algebra-equations', 'description' => 'اختبارات الجبر والمعادلات الخطية.'],
            ['name' => 'الهندسة', 'slug' => 'geometry', 'description' => 'اختبارات الهندسة والأشكال والقياس.'],
            ['name' => 'الإحصاء', 'slug' => 'statistics', 'description' => 'اختبارات الإحصاء وتحليل البيانات.'],
        ])->mapWithKeys(fn (array $attributes) => [$attributes['slug'] => ExamDepartment::updateOrCreate(['slug' => $attributes['slug']], [...$attributes, 'is_active' => true])]);

        $templates = [
            ['title' => 'اختبار الجبر الأول', 'slug' => 'algebra-first-test', 'department' => 'algebra-equations', 'grade' => 'الأول الإعدادي', 'status' => 'published'],
            ['title' => 'مراجعة الهندسة', 'slug' => 'geometry-review', 'department' => 'geometry', 'grade' => 'الثاني الإعدادي', 'status' => 'published'],
            ['title' => 'مسودة اختبار الإحصاء', 'slug' => 'statistics-draft', 'department' => 'statistics', 'grade' => 'الثالث الإعدادي', 'status' => 'draft'],
        ];

        foreach ($templates as $templateData) {
            $template = ExamTemplate::updateOrCreate(
                ['title' => $templateData['title']],
                [
                    'department_id' => $departments[$templateData['department']]->id,
                    'created_by' => $teacher->id,
                    'grade' => $templateData['grade'],
                    'duration_minutes' => 45,
                    'instructions' => 'اقرأ الأسئلة جيداً، واكتب خطوات الحل بوضوح قبل التسليم.',
                    'watermark_text' => 'الامتياز في الرياضيات · '.$templateData['grade'],
                    'watermark_opacity' => 12,
                    'status' => $templateData['status'],
                ],
            );

            $questions = [
                ['type' => 'mcq', 'prompt_html' => '<p>إذا كان س = ٣، فما قيمة ٢س + ١؟</p>', 'options' => ['٥', '٦', '٧', '٨'], 'correct_answer' => '٧', 'points' => 2, 'sort_order' => 0],
                ['type' => 'true_false', 'prompt_html' => '<p>مجموع زوايا المثلث يساوي ١٨٠ درجة.</p>', 'options' => ['صح', 'خطأ'], 'correct_answer' => 'صح', 'points' => 1, 'sort_order' => 1],
                ['type' => 'math', 'prompt_html' => '<p>حل المعادلة: س + ٤ = ٩.</p>', 'options' => null, 'correct_answer' => '٥', 'points' => 3, 'sort_order' => 2],
                ['type' => 'geometry', 'prompt_html' => '<p>احسب مساحة المستطيل الموضح.</p>', 'options' => ['shape' => 'rectangle', 'dimensions' => ['width' => '٦ سم', 'height' => '٤ سم'], 'labels' => ['title' => 'مستطيل بأبعاد معلومة']], 'correct_answer' => '٢٤ سم²', 'points' => 4, 'sort_order' => 3],
            ];

            foreach ($questions as $questionData) {
                ExamQuestion::updateOrCreate(
                    ['template_id' => $template->id, 'sort_order' => $questionData['sort_order']],
                    [...$questionData, 'template_id' => $template->id],
                );
            }

            if ($template->status !== 'published') {
                continue;
            }

            foreach (array_slice($students, 0, 2) as $index => $student) {
                $session = ExamSession::updateOrCreate(
                    ['template_id' => $template->id, 'student_id' => $student->id],
                    [
                        'started_at' => now()->subMinutes(18 + $index),
                        'submitted_at' => $index === 1 ? now()->subMinutes(3) : null,
                        'status' => $index === 1 ? 'submitted' : 'active',
                        'camera_required' => true,
                        'fullscreen_required' => true,
                        'focus_loss_count' => $index,
                        'last_event_at' => now()->subMinute(),
                    ],
                );

                foreach ($template->questions as $question) {
                    ExamSessionAnswer::updateOrCreate(
                        ['session_id' => $session->id, 'question_id' => $question->id],
                        ['answer' => $question->correct_answer, 'answered_at' => now()->subMinutes(4)],
                    );
                }

                ExamSessionEvent::updateOrCreate(
                    ['session_id' => $session->id, 'type' => 'camera_granted'],
                    ['metadata' => ['source' => 'seeded-demo', 'student' => $student->name], 'occurred_at' => $session->started_at],
                );

                if ($index === 1) {
                    ExamSessionEvent::updateOrCreate(
                        ['session_id' => $session->id, 'type' => 'submitted'],
                        ['metadata' => ['source' => 'seeded-demo'], 'occurred_at' => $session->submitted_at],
                    );
                }
            }
        }
    }

    private function seedPlugins(User $admin): void
    {
        $plugin = PluginProduct::updateOrCreate(
            ['slug' => 'attendance-insights'],
            [
                'name' => 'تحليلات الحضور',
                'description' => 'وحدة جاهزة لعرض مؤشرات الحضور اليومية داخل لوحة الإدارة.',
                'version' => '1.0.0',
                'module_name' => 'AttendanceInsights',
                'artifact_path' => 'plugins/artifacts/attendance-insights.zip',
                'artifact_sha256' => 'c91600cd5ec365daee974159f041af14b38d734bdd9430b60542e52ae485d21e',
                'price' => 0,
                'is_active' => true,
                'metadata' => ['category' => 'reports', 'language' => 'ar'],
            ],
        );

        PluginPurchase::updateOrCreate(
            ['user_id' => $admin->id, 'plugin_product_id' => $plugin->id],
            ['status' => 'completed', 'purchased_at' => now()],
        );

        PluginProduct::updateOrCreate(
            ['slug' => 'payment-center'],
            [
                'name' => 'مركز المدفوعات والاشتراكات',
                'description' => 'إدارة اشتراكات الطلاب، التحصيل، والتحويلات اليدوية عبر فودافون كاش وإنستاباي وفوري.',
                'version' => '1.0.0',
                'module_name' => 'CorePayments',
                'price' => 0,
                'is_active' => true,
                'metadata' => [
                    'category' => 'payments',
                    'language' => 'ar',
                    'core_feature' => true,
                    'available_roles' => ['admin', 'teacher'],
                ],
            ],
        );
    }

    private function seedStudents(): array
    {
        $coreStudents = [
            ['name' => 'أحمد محمد علي', 'phone' => '01010000001', 'parent_phone' => '01110000001', 'grade' => 'الأول الإعدادي', 'group' => 'المجموعة الأولى'],
            ['name' => 'سارة محمود حسن', 'phone' => '01010000002', 'parent_phone' => '01110000002', 'grade' => 'الأول الإعدادي', 'group' => 'المجموعة الأولى'],
            ['name' => 'يوسف خالد إبراهيم', 'phone' => '01010000003', 'parent_phone' => '01110000003', 'grade' => 'الثاني الإعدادي', 'group' => 'المجموعة الثانية'],
            ['name' => 'ملك أحمد السيد', 'phone' => '01010000004', 'parent_phone' => '01110000004', 'grade' => 'الثاني الإعدادي', 'group' => 'المجموعة الثانية'],
            ['name' => 'عمر سامح عبد الله', 'phone' => '01010000005', 'parent_phone' => '01110000005', 'grade' => 'الثالث الإعدادي', 'group' => 'المجموعة الثالثة'],
            ['name' => 'نورهان محمد سالم', 'phone' => '01010000006', 'parent_phone' => '01110000006', 'grade' => 'الثالث الإعدادي', 'group' => 'المجموعة الثالثة'],
        ];

        $baseStudents = collect($coreStudents)->map(function (array $attributes): Student {
            $student = Student::where('phone', $attributes['phone'])->first() ?? Student::factory()->create($attributes);
            $student->ensureQrToken();
            return $student;
        });

        $levelStudents = $this->seedLevelStudents(self::LEVEL_STUDENT_COUNT - $baseStudents->count());

        return [...$baseStudents->all(), ...$levelStudents->all()];
    }

    /** @return \Illuminate\Support\Collection<int, Student> */
    private function seedLevelStudents(int $count): \Illuminate\Support\Collection
    {
        $now = now();

        collect(range(0, $count - 1))
            ->chunk(self::LEVEL_STUDENT_UPSERT_CHUNK_SIZE)
            ->each(function (\Illuminate\Support\Collection $sequences) use ($now): void {
                $phones = $sequences
                    ->map(fn (int $sequence): string => StudentFactory::levelSeedPhone($sequence))
                    ->all();
                $existingQrTokens = Student::query()
                    ->whereIn('phone', $phones)
                    ->pluck('qr_token', 'phone');

                $records = $sequences->map(function (int $sequence) use ($existingQrTokens, $now): array {
                    $attributes = Student::factory()->levelSeed($sequence)->raw();
                    $phone = StudentFactory::levelSeedPhone($sequence);

                    return [
                        ...$attributes,
                        'phone' => $phone,
                        'qr_token' => $existingQrTokens->get($phone) ?: Str::random(64),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                Student::query()->upsert(
                    $records,
                    ['phone'],
                    ['name', 'group', 'grade', 'parent_phone', 'status', 'updated_at'],
                );
            });

        return Student::query()
            ->where('phone', 'like', '01098%')
            ->orderBy('phone')
            ->get();
    }

    /** @param array<int, Student> $students */
    private function seedAcademicGroups(array $students): void
    {
        foreach (collect($students)->groupBy(fn (Student $student): string => $student->grade.'|'.$student->group) as $gradeGroup => $groupStudents) {
            [$grade, $name] = explode('|', $gradeGroup, 2);
            $group = AcademicGroup::query()->updateOrCreate(
                ['grade' => $grade, 'name' => $name],
                ['is_active' => true],
            );
            $group->students()->sync($groupStudents->pluck('id')->all());
        }

        foreach (['الأول الإعدادي', 'الثاني الإعدادي', 'الثالث الإعدادي'] as $grade) {
            $legacyGroup = AcademicGroup::query()
                ->where('grade', $grade)
                ->where('name', 'مجموعة '.$grade)
                ->first();

            if ($legacyGroup !== null) {
                $legacyGroup->students()->detach();
                $legacyGroup->delete();
            }
        }
    }

    private function user(string $email, string $name, string $role, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => Hash::make($password)],
        );
    }

    private function seedSubscriptionPlatform(User $teacher): Tenant
    {
        SubscriptionPackage::updateOrCreate(['code' => 'starter'], [
            'name' => 'البداية', 'tagline' => 'للمعلم الذي يبدأ مركزه الرقمي',
            'description' => 'إدارة مركز واحد، حضور ذكي، شيتات، اختبارات، وتقارير أساسية.',
            'price_cents' => 49000, 'currency' => 'EGP', 'duration_days' => 30,
            'teacher_limit' => 1, 'student_limit' => 100,
            'features' => ['الحضور QR', 'الشيتات والاختبارات', 'تقارير أساسية'], 'is_active' => true, 'sort_order' => 10,
        ]);
        $growth = SubscriptionPackage::updateOrCreate(['code' => 'growth'], [
            'name' => 'النمو', 'tagline' => 'للأكاديميات التي تتوسع بثقة',
            'description' => 'يشمل المجموعات والإشعارات والتقارير المتقدمة وطلاباً أكثر.',
            'price_cents' => 99000, 'currency' => 'EGP', 'duration_days' => 30,
            'teacher_limit' => 5, 'student_limit' => 500,
            'features' => ['كل مزايا البداية', 'المجموعات والإشعارات', 'تقارير متقدمة'], 'is_active' => true, 'sort_order' => 20,
        ]);
        SubscriptionPackage::updateOrCreate(['code' => 'scale'], [
            'name' => 'التميز', 'tagline' => 'للمراكز متعددة المعلمين والطلاب',
            'description' => 'سعة أكبر، إعدادات تشغيل متقدمة، وإدارة مركز موسعة.',
            'price_cents' => 179000, 'currency' => 'EGP', 'duration_days' => 30,
            'teacher_limit' => 20, 'student_limit' => 2000,
            'features' => ['كل مزايا النمو', 'سعة موسعة', 'إدارة تشغيل متقدمة'], 'is_active' => true, 'sort_order' => 30,
        ]);

        $tenant = Tenant::updateOrCreate(['slug' => 'al-imtiaz-demo'], [
            'name' => 'الامتياز', 'domain_status' => 'pending',
        ]);
        $teacher->forceFill(['tenant_id' => $tenant->id])->save();
        TeacherDashboardLayout::updateOrCreate(['user_id' => $teacher->id], [
            'card_order' => ['attendance', 'exam_performance', 'payments', 'learning_flow'],
        ]);
        TenantSubscription::updateOrCreate(['tenant_id' => $tenant->id], [
            'subscription_package_id' => $growth->id, 'status' => 'active', 'payment_status' => 'paid',
            'starts_at' => now()->subDays(25), 'ends_at' => now()->addDays(5), 'paid_at' => now()->subDays(25),
        ]);

        return app(PostgresTenantSchemaProvisioner::class)->provision($tenant);
    }

    private function worksheet(array $attributes, User $teacher): Worksheet
    {
        return Worksheet::where('title', $attributes['title'])->first() ?? Worksheet::factory()->create([
            ...$attributes,
            'subject' => 'الرياضيات',
            'instructions' => 'حل الأسئلة بخطوات واضحة، ثم راجع إجاباتك قبل التسليم.',
            'due_at' => now()->addDays(7),
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
    }
}
