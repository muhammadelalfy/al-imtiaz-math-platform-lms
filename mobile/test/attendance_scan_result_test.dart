import 'package:flutter_test/flutter_test.dart';
import 'package:zewal_mobile/features/attendance/domain/entities/attendance_scan_result.dart';

void main() {
  test('maps a newly recorded attendance scan response', () {
    final AttendanceScanResult result = AttendanceScanResult.fromJson(
      <String, Object?>{
        'already_recorded': false,
        'attendance': <String, Object?>{
          'id': 31,
          'status': 'present',
          'date_at': '2026-08-24T08:30:00Z',
          'student': <String, Object?>{'name': 'مريم أحمد'},
        },
      },
    );

    expect(result.alreadyRecorded, isFalse);
    expect(result.attendance.id, 31);
    expect(result.attendance.status, 'present');
    expect(result.attendance.studentName, 'مريم أحمد');
  });

  test('maps an idempotent repeated scan response', () {
    final AttendanceScanResult result = AttendanceScanResult.fromJson(
      <String, Object?>{
        'already_recorded': true,
        'attendance': <String, Object?>{
          'id': 31,
          'status': 'present',
          'attendance_date': '2026-08-24',
        },
      },
    );

    expect(result.alreadyRecorded, isTrue);
    expect(result.attendance.recordedAt, '2026-08-24');
  });
}
