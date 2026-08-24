import '../../../core/errors/app_failure.dart';
import '../../../core/network/laravel_api_client.dart';
import '../domain/entities/attendance_scan_result.dart';
import '../domain/repositories/attendance_repository.dart';

class LaravelAttendanceRepository implements AttendanceRepository {
  LaravelAttendanceRepository(this._client);

  final LaravelApiClient _client;

  @override
  Future<AttendanceScanResult> scanQrPayload(String payload) async {
    final String normalizedPayload = payload.trim();
    if (normalizedPayload.length < 32 || normalizedPayload.length > 96) {
      throw const RequestFailure('رمز QR غير صالح للحضور.');
    }
    final Object? response = await _client.post(
      '/attendance/scan',
      data: <String, Object?>{'payload': normalizedPayload},
    );
    if (response is! Map<Object?, Object?>) {
      throw const RequestFailure('استجابة تسجيل الحضور غير صالحة.');
    }
    return AttendanceScanResult.fromJson(response.cast<String, Object?>());
  }
}
