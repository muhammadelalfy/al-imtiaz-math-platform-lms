<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorizationManagementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AcademicGroupController;
use App\Http\Controllers\Api\ExamResultController;
use App\Http\Controllers\Api\ExamManagementController;
use App\Http\Controllers\Api\NotificationCampaignController;
use App\Http\Controllers\Api\NotificationChannelSettingController;
use App\Http\Controllers\Api\NotificationInboxController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Http\Controllers\Api\ScheduledNotificationQueueController;
use App\Http\Controllers\Api\QuestionBankController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PluginStoreController;
use App\Http\Controllers\Api\PluginPaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherSlackLogDestinationController;
use App\Http\Controllers\Api\WorksheetController;
use App\Http\Controllers\Api\PublicSubscriptionController;
use App\Http\Controllers\Api\TeacherSubscriptionController;
use App\Http\Controllers\Api\TeacherAcademyIdentityController;
use App\Http\Controllers\Api\TeacherDashboardLayoutController;
use App\Http\Controllers\Api\SuperAdminSubscriptionController;
use Illuminate\Support\Facades\Route;
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/admin/login', fn (\Illuminate\Http\Request $request, AuthController $controller) => $controller->loginAsRole($request, 'admin'));
Route::post('/auth/teacher/login', fn (\Illuminate\Http\Request $request, AuthController $controller) => $controller->loginAsRole($request, 'teacher'));
Route::post('/auth/parent/login', fn (\Illuminate\Http\Request $request, AuthController $controller) => $controller->loginAsRole($request, 'parent'));
Route::post('/auth/student/login', fn (\Illuminate\Http\Request $request, AuthController $controller) => $controller->loginAsRole($request, 'student'));
Route::get('/public/subscription-packages', [PublicSubscriptionController::class, 'packages']);
Route::post('/public/teacher-register', [PublicSubscriptionController::class, 'registerTeacher']);
Route::post('/public/mock-tenant-registration', [PublicSubscriptionController::class, 'mockRegistration'])
    ->middleware('throttle:3,60');
Route::post('/scheduled/notifications/drain', ScheduledNotificationQueueController::class)
    ->middleware('signed')
    ->name('scheduled.notifications.drain');
Route::middleware(['auth:sanctum', \App\Http\Middleware\DispatchTeacherSlackRequestLog::class])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::middleware('role.guard:teacher')->get('/teacher/subscription', [TeacherSubscriptionController::class, 'show']);
    Route::middleware('role.guard:teacher')
        ->prefix('teacher/academy-identity')
        ->group(function (): void {
            Route::get('/', [TeacherAcademyIdentityController::class, 'show']);
            Route::put('/', [TeacherAcademyIdentityController::class, 'update']);
        });
    Route::middleware('role.guard:teacher')
        ->prefix('teacher/dashboard-layout')
        ->group(function (): void {
            Route::get('/', [TeacherDashboardLayoutController::class, 'show']);
            Route::put('/', [TeacherDashboardLayoutController::class, 'update']);
            Route::delete('/', [TeacherDashboardLayoutController::class, 'destroy']);
        });
    Route::prefix('super-admin')->group(function (): void {
        Route::get('/overview', [SuperAdminSubscriptionController::class, 'overview']);
        Route::get('/packages', [SuperAdminSubscriptionController::class, 'packages']);
        Route::post('/packages', [SuperAdminSubscriptionController::class, 'storePackage']);
        Route::put('/packages/{subscriptionPackage}', [SuperAdminSubscriptionController::class, 'updatePackage']);
        Route::get('/subscriptions', [SuperAdminSubscriptionController::class, 'subscriptions']);
        Route::put('/subscriptions/{tenantSubscription}', [SuperAdminSubscriptionController::class, 'updateSubscription']);
        Route::put('/tenants/{tenant}/domain', [SuperAdminSubscriptionController::class, 'updateTenantDomain']);
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/sync/snapshot', [OfflineSyncController::class, 'snapshot']);
    Route::post('/sync/operations', [OfflineSyncController::class, 'reconcile']);
    Route::get('/notifications', [NotificationInboxController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationInboxController::class, 'markRead']);

    Route::middleware('role.guard:teacher')
        ->prefix('teacher/slack-log-destination')
        ->group(function (): void {
            Route::get('/', [TeacherSlackLogDestinationController::class, 'show']);
            Route::put('/', [TeacherSlackLogDestinationController::class, 'update']);
            Route::delete('/', [TeacherSlackLogDestinationController::class, 'destroy']);
        });

    Route::middleware(['role.guard:admin,teacher', 'can:notifications.channels.manage'])
        ->prefix('staff/notification-channels')
        ->group(function (): void {
            Route::get('/', [NotificationChannelSettingController::class, 'index']);
            Route::put('/{notificationChannelSetting}', [NotificationChannelSettingController::class, 'update']);
        });

    Route::middleware(['role.guard:admin,teacher', 'can:groups.manage'])
        ->prefix('staff/academic-groups')
        ->group(function (): void {
            Route::get('/', [AcademicGroupController::class, 'index']);
            Route::post('/', [AcademicGroupController::class, 'store']);
            Route::get('/{academicGroup}', [AcademicGroupController::class, 'show']);
            Route::put('/{academicGroup}', [AcademicGroupController::class, 'update']);
            Route::delete('/{academicGroup}', [AcademicGroupController::class, 'destroy']);
            Route::put('/{academicGroup}/students', [AcademicGroupController::class, 'syncStudents']);
        });

    Route::middleware(['role.guard:admin,teacher', 'can:notifications.send'])
        ->prefix('staff/notifications')
        ->group(function (): void {
            Route::get('/audience-catalog', [NotificationCampaignController::class, 'audienceCatalog']);
            Route::get('/', [NotificationCampaignController::class, 'index']);
            Route::post('/', [NotificationCampaignController::class, 'store']);
        });

    Route::middleware(['role.guard:admin,teacher', 'can:authorization.manage'])
        ->prefix('staff/authorization')
        ->group(function (): void {
            Route::get('/catalog', [AuthorizationManagementController::class, 'index']);
            Route::post('/permissions', [AuthorizationManagementController::class, 'storePermission']);
            Route::put('/permissions/{permission}', [AuthorizationManagementController::class, 'updatePermission']);
            Route::delete('/permissions/{permission}', [AuthorizationManagementController::class, 'destroyPermission']);
            Route::post('/roles', [AuthorizationManagementController::class, 'storeRole']);
            Route::put('/roles/{role}', [AuthorizationManagementController::class, 'updateRole']);
            Route::delete('/roles/{role}', [AuthorizationManagementController::class, 'destroyRole']);
            Route::put('/staff/{user}/roles', [AuthorizationManagementController::class, 'syncStaffRoles']);
        });
    Route::apiResource('students', StudentController::class)->only(['index','store','show','update','destroy']);
    Route::get('/students/{student}/qr', [StudentController::class, 'qr']);
    Route::apiResource('worksheets', WorksheetController::class)->only(['index','store','show']);
    Route::post('/worksheets/{worksheet}/assign', [WorksheetController::class, 'assign']);
    Route::post('/assignments/{assignment}/submit', [WorksheetController::class, 'submit']);
    Route::apiResource('attendance', AttendanceController::class)->only(['index','store','update','destroy']);
    Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
    Route::apiResource('exams', ExamResultController::class)->only(['index','store','update','destroy']);
    Route::get('/exam-departments', [ExamManagementController::class, 'departments']);
    Route::post('/exam-departments', [ExamManagementController::class, 'storeDepartment']);
    Route::put('/exam-departments/{department}', [ExamManagementController::class, 'updateDepartment']);
    Route::delete('/exam-departments/{department}', [ExamManagementController::class, 'destroyDepartment']);
    Route::apiResource('question-bank', QuestionBankController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->parameters(['question-bank' => 'questionBankQuestion']);
    Route::get('/exam-templates', [ExamManagementController::class, 'templates']);
    Route::post('/exam-templates', [ExamManagementController::class, 'storeTemplate']);
    Route::put('/exam-templates/{template}', [ExamManagementController::class, 'updateTemplate']);
    Route::delete('/exam-templates/{template}', [ExamManagementController::class, 'destroyTemplate']);
    Route::get('/exam-templates/{template}/pdf', [ExamManagementController::class, 'downloadPdf']);
    Route::post('/exam-templates/{template}/start', [ExamManagementController::class, 'startSession']);
    Route::post('/exam-sessions/{session}/events', [ExamManagementController::class, 'event']);
    Route::post('/exam-sessions/{session}/answers', [ExamManagementController::class, 'answer']);
    Route::post('/exam-sessions/{session}/submit', [ExamManagementController::class, 'submit']);
    Route::apiResource('payments', PaymentController::class)->only(['index','store','update','destroy']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/plugins', [PluginStoreController::class, 'index']);
    Route::get('/plugins/installed', [PluginStoreController::class, 'installed']);
    Route::post('/plugins/{plugin}/purchase', [PluginStoreController::class, 'purchase']);
    Route::get('/plugin-payment-methods', [PluginPaymentController::class, 'methods']);
    Route::post('/plugins/{plugin}/checkout', [PluginPaymentController::class, 'checkout']);
    Route::get('/plugin-payments', [PluginPaymentController::class, 'history']);
    Route::post('/plugin-payments/{payment}/reference', [PluginPaymentController::class, 'submitReference']);
    Route::get('/admin/plugin-payment-methods', [PluginPaymentController::class, 'adminMethods']);
    Route::put('/admin/plugin-payment-methods/{method}', [PluginPaymentController::class, 'updateMethod']);
    Route::get('/admin/plugin-payments/review-queue', [PluginPaymentController::class, 'reviewQueue']);
    Route::post('/admin/plugin-payments/{payment}/approve', [PluginPaymentController::class, 'approve']);
    Route::post('/admin/plugin-payments/{payment}/reject', [PluginPaymentController::class, 'reject']);
    Route::post('/plugins/{plugin}/install', [PluginStoreController::class, 'install']);
    Route::delete('/plugins/{plugin}/install', [PluginStoreController::class, 'uninstall']);
});
