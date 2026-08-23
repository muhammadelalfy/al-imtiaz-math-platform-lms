import '../entities/dashboard_snapshot.dart';

abstract interface class DashboardRepository {
  Future<DashboardSnapshot> loadSnapshot({
    required int userId,
    required bool includePlatformTelemetry,
    bool forceRefresh = false,
  });

  Future<StudentQrCode> fetchStudentQr(int studentId);
}
