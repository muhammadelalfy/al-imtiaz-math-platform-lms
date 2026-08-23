import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../app/providers.dart';
import '../../auth/domain/entities/app_user.dart';
import '../domain/entities/dashboard_snapshot.dart';

class MobileDashboard extends ConsumerStatefulWidget {
  const MobileDashboard({super.key, required this.user});

  final AppUser user;

  @override
  ConsumerState<MobileDashboard> createState() => _MobileDashboardState();
}

class _MobileDashboardState extends ConsumerState<MobileDashboard> {
  var _selectedIndex = 0;

  DashboardScope get _scope => DashboardScope(
        userId: widget.user.id,
        includePlatformTelemetry: widget.user.isSuperAdmin,
      );

  List<_DashboardTab> get _tabs {
    if (widget.user.role == UserRole.teacher || widget.user.role == UserRole.admin) {
      return const <_DashboardTab>[
        _DashboardTab('نظرة عامة', Icons.grid_view_rounded),
        _DashboardTab('الطلاب', Icons.people_outline_rounded),
        _DashboardTab('الحضور', Icons.qr_code_scanner_rounded),
        _DashboardTab('التعلم', Icons.menu_book_outlined),
        _DashboardTab('التقارير', Icons.assessment_outlined),
      ];
    }
    return const <_DashboardTab>[
      _DashboardTab('نظرة عامة', Icons.grid_view_rounded),
      _DashboardTab('التعلم', Icons.menu_book_outlined),
      _DashboardTab('النتائج', Icons.fact_check_outlined),
      _DashboardTab('المدفوعات', Icons.payments_outlined),
    ];
  }

  void _openNotifications(DashboardSnapshot snapshot) {
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (BuildContext context) => _NotificationsSheet(
        notifications: snapshot.notifications,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final AsyncValue<DashboardSnapshot> snapshot = ref.watch(
      dashboardSnapshotProvider(_scope),
    );
    final String organization = widget.user.academyName?.trim().isNotEmpty ?? false
        ? widget.user.academyName!
        : 'زويل التعليمية';

    return Scaffold(
      appBar: AppBar(
        title: Text(organization),
        actions: <Widget>[
          snapshot.when(
            data: (DashboardSnapshot value) => IconButton(
              onPressed: () => _openNotifications(value),
              icon: Badge(
                isLabelVisible: value.notifications.isNotEmpty,
                label: Text(value.notifications.length.toString()),
                child: const Icon(Icons.notifications_none_rounded),
              ),
              tooltip: 'الإشعارات',
            ),
            error: (_, _) => const SizedBox.shrink(),
            loading: () => const SizedBox.shrink(),
          ),
          IconButton(
            onPressed: () => ref.read(sessionControllerProvider.notifier).signOut(),
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'تسجيل الخروج',
          ),
        ],
      ),
      body: snapshot.when(
        loading: () => const _DashboardLoading(),
        error: (Object error, StackTrace stackTrace) => _DashboardError(
          onRetry: () => ref.invalidate(dashboardSnapshotProvider(_scope)),
        ),
        data: (DashboardSnapshot value) => RefreshIndicator.adaptive(
          onRefresh: () async {
            ref.invalidate(dashboardSnapshotProvider(_scope));
            await ref.read(dashboardSnapshotProvider(_scope).future);
          },
          child: _DashboardBody(
            user: widget.user,
            snapshot: value,
            selectedIndex: _selectedIndex,
          ),
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (int index) => setState(() => _selectedIndex = index),
        destinations: _tabs
            .map(
              (_DashboardTab tab) => NavigationDestination(
                icon: Icon(tab.icon),
                label: tab.label,
              ),
            )
            .toList(growable: false),
      ),
    );
  }
}

class _DashboardBody extends StatelessWidget {
  const _DashboardBody({
    required this.user,
    required this.snapshot,
    required this.selectedIndex,
  });

  final AppUser user;
  final DashboardSnapshot snapshot;
  final int selectedIndex;

  @override
  Widget build(BuildContext context) {
    final bool canManageLearners = user.role == UserRole.teacher || user.role == UserRole.admin;
    if (canManageLearners) {
      return switch (selectedIndex) {
        0 => _OverviewView(user: user, snapshot: snapshot),
        1 => _StudentsView(students: snapshot.students),
        2 => _AttendanceView(records: snapshot.attendance),
        3 => _LearningView(items: snapshot.worksheets),
        _ => _ReportsView(exams: snapshot.exams, payments: snapshot.payments),
      };
    }
    return switch (selectedIndex) {
      0 => _OverviewView(user: user, snapshot: snapshot),
      1 => _LearningView(items: snapshot.worksheets),
      2 => _ExamResultsView(results: snapshot.exams),
      _ => _PaymentsView(payments: snapshot.payments),
    };
  }
}

class _OverviewView extends StatelessWidget {
  const _OverviewView({required this.user, required this.snapshot});

  final AppUser user;
  final DashboardSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(20),
      children: <Widget>[
        Text('أهلاً، ${user.name}', style: theme.textTheme.headlineSmall),
        const SizedBox(height: 6),
        Text('${user.role.label} · بيانات مباشرة من منصة زويل'),
        const SizedBox(height: 20),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: <Widget>[
            _MetricCard(label: 'الطلاب', value: snapshot.students.length.toString(), icon: Icons.people_outline_rounded),
            _MetricCard(label: 'الحضور المسجل', value: snapshot.attendance.length.toString(), icon: Icons.how_to_reg_outlined),
            _MetricCard(label: 'الشيتات', value: snapshot.worksheets.length.toString(), icon: Icons.menu_book_outlined),
            _MetricCard(label: 'الاختبارات', value: snapshot.exams.length.toString(), icon: Icons.fact_check_outlined),
          ],
        ),
        const SizedBox(height: 20),
        _SectionCard(
          title: 'أحدث الحضور',
          child: snapshot.attendance.isEmpty
              ? const _EmptyState(message: 'لا توجد سجلات حضور متاحة حالياً.')
              : Column(
                  children: snapshot.attendance.take(3).map(
                    (AttendanceRecord item) => _AttendanceTile(record: item),
                  ).toList(growable: false),
                ),
        ),
        if (user.isSuperAdmin && snapshot.platform != null) ...<Widget>[
          const SizedBox(height: 16),
          _PlatformTelemetryCard(telemetry: snapshot.platform!),
        ],
      ],
    );
  }
}

class _StudentsView extends ConsumerWidget {
  const _StudentsView({required this.students});

  final List<StudentSummary> students;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (students.isEmpty) return const _EmptyState(message: 'لا توجد سجلات طلاب متاحة.');
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: students.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, int index) {
        final StudentSummary student = students[index];
        return Card(
          child: ListTile(
            leading: CircleAvatar(child: Text(student.name.characters.first)),
            title: Text(student.name),
            subtitle: Text('${student.grade} · ${student.group}'),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Text('${student.assignmentsCount} شيت'),
                IconButton(
                  onPressed: () => showModalBottomSheet<void>(
                    context: context,
                    showDragHandle: true,
                    builder: (BuildContext context) => _StudentQrSheet(
                      student: student,
                      loadQr: () => ref
                          .read(dashboardRepositoryProvider)
                          .fetchStudentQr(student.id),
                    ),
                  ),
                  icon: const Icon(Icons.qr_code_rounded),
                  tooltip: 'رمز حضور الطالب',
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _StudentQrSheet extends StatelessWidget {
  const _StudentQrSheet({required this.student, required this.loadQr});

  final StudentSummary student;
  final Future<StudentQrCode> Function() loadQr;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: FutureBuilder<StudentQrCode>(
        future: loadQr(),
        builder: (BuildContext context, AsyncSnapshot<StudentQrCode> snapshot) {
          if (snapshot.hasError) {
            return const SizedBox(
              height: 260,
              child: _EmptyState(message: 'تعذر تحميل رمز الحضور. حاول مرة أخرى.'),
            );
          }
          if (!snapshot.hasData) {
            return const SizedBox(
              height: 260,
              child: Center(child: CircularProgressIndicator.adaptive()),
            );
          }
          final StudentQrCode qr = snapshot.requireData;
          return Padding(
            padding: const EdgeInsets.fromLTRB(24, 0, 24, 28),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Text(student.name, style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 16),
                QrImageView(
                  data: qr.payload,
                  size: 216,
                  errorStateBuilder: (_, _) => const Icon(Icons.error_outline),
                ),
                const SizedBox(height: 12),
                const Text('اعرض الرمز ليتم تسجيل الحضور في منصة زويل.'),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _AttendanceView extends StatelessWidget {
  const _AttendanceView({required this.records});

  final List<AttendanceRecord> records;

  @override
  Widget build(BuildContext context) {
    if (records.isEmpty) return const _EmptyState(message: 'لا توجد سجلات حضور متاحة.');
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: records.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, int index) => Card(child: _AttendanceTile(record: records[index])),
    );
  }
}

class _LearningView extends StatelessWidget {
  const _LearningView({required this.items});

  final List<LearningItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) return const _EmptyState(message: 'لا توجد شيتات متاحة حالياً.');
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: items.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, int index) {
        final LearningItem item = items[index];
        return Card(
          child: ListTile(
            leading: const Icon(Icons.menu_book_outlined),
            title: Text(item.title),
            subtitle: Text('${item.subject} · ${item.grade}'),
            trailing: Text(item.status == 'published' ? 'منشور' : 'مسودة'),
          ),
        );
      },
    );
  }
}

class _ReportsView extends StatelessWidget {
  const _ReportsView({required this.exams, required this.payments});

  final List<ExamResultSummary> exams;
  final List<PaymentSummary> payments;

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        _SectionCard(title: 'أحدث نتائج الاختبارات', child: _ExamList(results: exams, limit: 4)),
        const SizedBox(height: 16),
        _SectionCard(title: 'المدفوعات', child: _PaymentList(payments: payments, limit: 4)),
      ],
    );
  }
}

class _ExamResultsView extends StatelessWidget {
  const _ExamResultsView({required this.results});

  final List<ExamResultSummary> results;

  @override
  Widget build(BuildContext context) {
    return _PaddedList(child: _ExamList(results: results));
  }
}

class _PaymentsView extends StatelessWidget {
  const _PaymentsView({required this.payments});

  final List<PaymentSummary> payments;

  @override
  Widget build(BuildContext context) {
    return _PaddedList(child: _PaymentList(payments: payments));
  }
}

class _PaddedList extends StatelessWidget {
  const _PaddedList({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: <Widget>[child],
    );
  }
}

class _ExamList extends StatelessWidget {
  const _ExamList({required this.results, this.limit});

  final List<ExamResultSummary> results;
  final int? limit;

  @override
  Widget build(BuildContext context) {
    final List<ExamResultSummary> shown = limit == null ? results : results.take(limit!).toList(growable: false);
    if (shown.isEmpty) return const _EmptyState(message: 'لا توجد نتائج اختبارات متاحة.');
    return Column(
      children: shown
          .map(
            (ExamResultSummary result) => ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.fact_check_outlined),
              title: Text(result.title),
              subtitle: Text(result.studentName ?? _formatDate(result.takenAt)),
              trailing: Text('${result.score.toStringAsFixed(0)} / ${result.maxScore.toStringAsFixed(0)}'),
            ),
          )
          .toList(growable: false),
    );
  }
}

class _PaymentList extends StatelessWidget {
  const _PaymentList({required this.payments, this.limit});

  final List<PaymentSummary> payments;
  final int? limit;

  @override
  Widget build(BuildContext context) {
    final List<PaymentSummary> shown = limit == null ? payments : payments.take(limit!).toList(growable: false);
    if (shown.isEmpty) return const _EmptyState(message: 'لا توجد مدفوعات متاحة.');
    return Column(
      children: shown
          .map(
            (PaymentSummary payment) => ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(payment.status == 'paid' ? Icons.check_circle_outline : Icons.schedule_outlined),
              title: Text(payment.studentName ?? 'دفعة تعليمية'),
              subtitle: Text(_formatDate(payment.dueAt)),
              trailing: Text('${payment.amount.toStringAsFixed(0)} ج.م'),
            ),
          )
          .toList(growable: false),
    );
  }
}

class _AttendanceTile extends StatelessWidget {
  const _AttendanceTile({required this.record});

  final AttendanceRecord record;

  @override
  Widget build(BuildContext context) {
    final bool present = record.status == 'present';
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
      leading: Icon(present ? Icons.check_circle_outline : Icons.schedule_outlined),
      title: Text(record.studentName ?? 'سجل حضور'),
      subtitle: Text(_formatDate(record.recordedAt)),
      trailing: Text(_attendanceLabel(record.status)),
    );
  }
}

class _NotificationsSheet extends StatelessWidget {
  const _NotificationsSheet({required this.notifications});

  final List<NotificationItem> notifications;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SizedBox(
        height: 440,
        child: notifications.isEmpty
            ? const _EmptyState(message: 'لا توجد إشعارات جديدة.')
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: notifications.length,
                separatorBuilder: (_, _) => const Divider(),
                itemBuilder: (_, int index) {
                  final NotificationItem item = notifications[index];
                  return ListTile(
                    leading: Icon(item.readAt == null ? Icons.mark_email_unread_outlined : Icons.mail_outline),
                    title: Text(item.title),
                    subtitle: Text(item.body),
                  );
                },
              ),
      ),
    );
  }
}

class _PlatformTelemetryCard extends StatelessWidget {
  const _PlatformTelemetryCard({required this.telemetry});

  final PlatformTelemetry telemetry;

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: 'مركز التحكم بالمنصة',
      child: Wrap(
        spacing: 12,
        runSpacing: 12,
        children: <Widget>[
          _MetricCard(label: 'زمن قاعدة البيانات', value: '${telemetry.databaseLatencyMs.toStringAsFixed(0)} مللي ث', icon: Icons.storage_outlined),
          _MetricCard(label: 'عمليات قيد الانتظار', value: telemetry.pendingJobs.toString(), icon: Icons.pending_actions_outlined),
          _MetricCard(label: 'ذاكرة الذروة', value: '${telemetry.memoryPeakMb.toStringAsFixed(1)} م.ب', icon: Icons.memory_outlined),
          _MetricCard(label: 'المؤسسات', value: telemetry.tenants.toString(), icon: Icons.apartment_outlined),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.label, required this.value, required this.icon});

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 156,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Icon(icon, color: Theme.of(context).colorScheme.primary),
              const SizedBox(height: 16),
              Text(value, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 4),
              Text(label, style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            child,
          ],
        ),
      ),
    );
  }
}

class _DashboardLoading extends StatelessWidget {
  const _DashboardLoading();

  @override
  Widget build(BuildContext context) {
    return const Center(child: CircularProgressIndicator.adaptive());
  }
}

class _DashboardError extends StatelessWidget {
  const _DashboardError({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            const Icon(Icons.cloud_off_outlined, size: 42),
            const SizedBox(height: 12),
            const Text('تعذر تحميل بياناتك حالياً.'),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(child: Text(message, textAlign: TextAlign.center)),
    );
  }
}

class _DashboardTab {
  const _DashboardTab(this.label, this.icon);

  final String label;
  final IconData icon;
}

String _attendanceLabel(String status) => switch (status) {
      'present' => 'حاضر',
      'late' => 'متأخر',
      _ => 'غائب',
    };

String _formatDate(String value) {
  final DateTime? date = DateTime.tryParse(value);
  return date == null ? '—' : DateFormat.yMMMd('ar_EG').format(date.toLocal());
}
