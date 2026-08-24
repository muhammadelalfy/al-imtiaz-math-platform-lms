import '../../../dashboard/domain/entities/dashboard_snapshot.dart';

class AttendanceScanResult {
  const AttendanceScanResult({
    required this.alreadyRecorded,
    required this.attendance,
  });

  factory AttendanceScanResult.fromJson(Map<String, Object?> json) {
    final Object? attendanceValue = json['attendance'];
    if (attendanceValue is! Map<Object?, Object?>) {
      throw const FormatException('Missing attendance scan record.');
    }
    return AttendanceScanResult(
      alreadyRecorded: json['already_recorded'] as bool? ?? false,
      attendance: AttendanceRecord.fromJson(attendanceValue.cast<String, Object?>()),
    );
  }

  final bool alreadyRecorded;
  final AttendanceRecord attendance;
}
