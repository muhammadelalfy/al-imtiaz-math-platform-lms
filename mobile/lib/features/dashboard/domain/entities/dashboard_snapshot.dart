class StudentSummary {
  const StudentSummary({
    required this.id,
    required this.name,
    required this.grade,
    required this.group,
    required this.status,
    required this.assignmentsCount,
  });

  factory StudentSummary.fromJson(Map<String, Object?> json) => StudentSummary(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String? ?? '—',
        grade: json['grade'] as String? ?? '—',
        group: json['group'] as String? ?? '—',
        status: json['status'] as String? ?? 'average',
        assignmentsCount: (json['assignments_count'] as num?)?.toInt() ?? 0,
      );

  final int id;
  final String name;
  final String grade;
  final String group;
  final String status;
  final int assignmentsCount;
}

class StudentQrCode {
  const StudentQrCode({
    required this.studentId,
    required this.payload,
    required this.generatedAt,
  });

  factory StudentQrCode.fromJson(Map<String, Object?> json) => StudentQrCode(
        studentId: (json['student_id'] as num).toInt(),
        payload: json['payload'] as String,
        generatedAt: json['generated_at'] as String,
      );

  final int studentId;
  final String payload;
  final String generatedAt;
}

class AttendanceRecord {
  const AttendanceRecord({
    required this.id,
    required this.status,
    required this.recordedAt,
    this.studentName,
  });

  factory AttendanceRecord.fromJson(Map<String, Object?> json) {
    final Map<String, Object?>? student = _asMapOrNull(json['student']);
    return AttendanceRecord(
      id: (json['id'] as num).toInt(),
      status: json['status'] as String? ?? 'absent',
      recordedAt: json['date_at'] as String? ?? json['attendance_date'] as String? ?? '',
      studentName: student?['name'] as String?,
    );
  }

  final int id;
  final String status;
  final String recordedAt;
  final String? studentName;
}

class LearningItem {
  const LearningItem({
    required this.id,
    required this.title,
    required this.subject,
    required this.grade,
    required this.status,
  });

  factory LearningItem.fromJson(Map<String, Object?> json) => LearningItem(
        id: (json['id'] as num).toInt(),
        title: json['title'] as String? ?? '—',
        subject: json['subject'] as String? ?? '—',
        grade: json['grade'] as String? ?? '—',
        status: json['status'] as String? ?? 'draft',
      );

  final int id;
  final String title;
  final String subject;
  final String grade;
  final String status;
}

class ExamResultSummary {
  const ExamResultSummary({
    required this.id,
    required this.title,
    required this.score,
    required this.maxScore,
    required this.takenAt,
    this.studentName,
  });

  factory ExamResultSummary.fromJson(Map<String, Object?> json) {
    final Map<String, Object?>? student = _asMapOrNull(json['student']);
    return ExamResultSummary(
      id: (json['id'] as num).toInt(),
      title: json['title'] as String? ?? '—',
      score: (json['score'] as num?)?.toDouble() ?? 0,
      maxScore: (json['max_score'] as num?)?.toDouble() ?? 0,
      takenAt: json['taken_at'] as String? ?? '',
      studentName: student?['name'] as String?,
    );
  }

  final int id;
  final String title;
  final double score;
  final double maxScore;
  final String takenAt;
  final String? studentName;
}

class PaymentSummary {
  const PaymentSummary({
    required this.id,
    required this.amount,
    required this.status,
    required this.dueAt,
    this.studentName,
  });

  factory PaymentSummary.fromJson(Map<String, Object?> json) {
    final Map<String, Object?>? student = _asMapOrNull(json['student']);
    return PaymentSummary(
      id: (json['id'] as num).toInt(),
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      status: json['status'] as String? ?? 'pending',
      dueAt: json['due_at'] as String? ?? '',
      studentName: student?['name'] as String?,
    );
  }

  final int id;
  final double amount;
  final String status;
  final String dueAt;
  final String? studentName;
}

class NotificationItem {
  const NotificationItem({
    required this.id,
    required this.title,
    required this.body,
    this.createdAt,
    this.readAt,
  });

  factory NotificationItem.fromJson(Map<String, Object?> json) => NotificationItem(
        id: json['id'].toString(),
        title: json['title'] as String? ?? 'إشعار',
        body: json['body'] as String? ?? '',
        createdAt: json['created_at'] as String?,
        readAt: json['read_at'] as String?,
      );

  final String id;
  final String title;
  final String body;
  final String? createdAt;
  final String? readAt;
}

class PlatformTelemetry {
  const PlatformTelemetry({
    required this.databaseLatencyMs,
    required this.pendingJobs,
    required this.failedJobs,
    required this.memoryPeakMb,
    required this.tenants,
    required this.activeSubscriptions,
  });

  factory PlatformTelemetry.fromJson(Map<String, Object?> json) {
    final Map<String, Object?> health = _asMap(json['health']);
    final Map<String, Object?> queue = _asMap(json['queue']);
    final Map<String, Object?> runtime = _asMap(json['runtime']);
    final Map<String, Object?> counts = _asMap(json['counts']);
    return PlatformTelemetry(
      databaseLatencyMs: (health['database_latency_ms'] as num?)?.toDouble() ?? 0,
      pendingJobs: (queue['pending_jobs'] as num?)?.toInt() ?? 0,
      failedJobs: (queue['failed_jobs'] as num?)?.toInt() ?? 0,
      memoryPeakMb: (runtime['memory_peak_mb'] as num?)?.toDouble() ?? 0,
      tenants: (counts['tenants'] as num?)?.toInt() ?? 0,
      activeSubscriptions: (counts['active_subscriptions'] as num?)?.toInt() ?? 0,
    );
  }

  final double databaseLatencyMs;
  final int pendingJobs;
  final int failedJobs;
  final double memoryPeakMb;
  final int tenants;
  final int activeSubscriptions;
}

class DashboardSnapshot {
  const DashboardSnapshot({
    required this.students,
    required this.attendance,
    required this.worksheets,
    required this.exams,
    required this.payments,
    required this.notifications,
    this.platform,
  });

  final List<StudentSummary> students;
  final List<AttendanceRecord> attendance;
  final List<LearningItem> worksheets;
  final List<ExamResultSummary> exams;
  final List<PaymentSummary> payments;
  final List<NotificationItem> notifications;
  final PlatformTelemetry? platform;
}

Map<String, Object?> _asMap(Object? value) {
  return value is Map<Object?, Object?> ? value.cast<String, Object?>() : const <String, Object?>{};
}

Map<String, Object?>? _asMapOrNull(Object? value) {
  return value is Map<Object?, Object?> ? value.cast<String, Object?>() : null;
}
