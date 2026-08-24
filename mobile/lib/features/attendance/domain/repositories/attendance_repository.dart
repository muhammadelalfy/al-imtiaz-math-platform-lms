import '../entities/attendance_scan_result.dart';

abstract interface class AttendanceRepository {
  Future<AttendanceScanResult> scanQrPayload(String payload);
}
