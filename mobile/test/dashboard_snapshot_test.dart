import 'package:flutter_test/flutter_test.dart';
import 'package:zewal_mobile/features/dashboard/domain/entities/dashboard_snapshot.dart';

void main() {
  group('PlatformTelemetry', () {
    test('maps only real Laravel overview telemetry fields', () {
      final PlatformTelemetry telemetry = PlatformTelemetry.fromJson(
        <String, Object?>{
          'health': <String, Object?>{'database_latency_ms': 8.4},
          'queue': <String, Object?>{'pending_jobs': 3, 'failed_jobs': 1},
          'runtime': <String, Object?>{'memory_peak_mb': 42.5},
          'counts': <String, Object?>{
            'tenants': 7,
            'active_subscriptions': 5,
          },
        },
      );

      expect(telemetry.databaseLatencyMs, 8.4);
      expect(telemetry.pendingJobs, 3);
      expect(telemetry.failedJobs, 1);
      expect(telemetry.memoryPeakMb, 42.5);
      expect(telemetry.tenants, 7);
      expect(telemetry.activeSubscriptions, 5);
    });
  });

  test('maps student summary counters without exposing sensitive contacts', () {
    final StudentSummary student = StudentSummary.fromJson(
      <String, Object?>{
        'id': 12,
        'name': 'مريم أحمد',
        'grade': 'ثانية ثانوي',
        'group': 'بنات',
        'status': 'excellent',
        'assignments_count': 4,
        'phone': 'not-mapped',
      },
    );

    expect(student.id, 12);
    expect(student.name, 'مريم أحمد');
    expect(student.assignmentsCount, 4);
  });
}
