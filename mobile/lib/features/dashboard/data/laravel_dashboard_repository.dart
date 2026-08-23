import '../../../core/errors/app_failure.dart';
import '../../../core/network/laravel_api_client.dart';
import '../domain/entities/dashboard_snapshot.dart';
import '../domain/repositories/dashboard_repository.dart';

class LaravelDashboardRepository implements DashboardRepository {
  LaravelDashboardRepository(this._client);

  static const Duration _cacheLifetime = Duration(seconds: 20);

  final LaravelApiClient _client;
  final Map<int, _CachedSnapshot> _cache = <int, _CachedSnapshot>{};

  @override
  Future<DashboardSnapshot> loadSnapshot({
    required int userId,
    required bool includePlatformTelemetry,
    bool forceRefresh = false,
  }) async {
    final _CachedSnapshot? cached = _cache[userId];
    if (!forceRefresh && cached != null && !cached.isExpired) return cached.value;

    final List<Object?> responses = await Future.wait<Object?>(<Future<Object?>>[
      _client.get('/students?page=1'),
      _client.get('/attendance'),
      _client.get('/worksheets'),
      _client.get('/exams'),
      _client.get('/payments'),
      _client.get('/notifications'),
      if (includePlatformTelemetry) _client.get('/super-admin/overview'),
    ]);

    final DashboardSnapshot snapshot = DashboardSnapshot(
      students: _collection(responses[0]).map(StudentSummary.fromJson).toList(growable: false),
      attendance: _collection(responses[1]).map(AttendanceRecord.fromJson).toList(growable: false),
      worksheets: _collection(responses[2]).map(LearningItem.fromJson).toList(growable: false),
      exams: _collection(responses[3]).map(ExamResultSummary.fromJson).toList(growable: false),
      payments: _collection(responses[4]).map(PaymentSummary.fromJson).toList(growable: false),
      notifications: _collection(responses[5]).map(NotificationItem.fromJson).toList(growable: false),
      platform: includePlatformTelemetry
          ? PlatformTelemetry.fromJson(_map(responses[6]))
          : null,
    );
    _cache[userId] = _CachedSnapshot(snapshot, DateTime.timestamp());
    return snapshot;
  }

  @override
  Future<StudentQrCode> fetchStudentQr(int studentId) async {
    final Object? response = await _client.get('/students/$studentId/qr');
    return StudentQrCode.fromJson(_map(response));
  }

  List<Map<String, Object?>> _collection(Object? value) {
    final Object? rawItems = value is Map<Object?, Object?> ? value['data'] : value;
    if (rawItems is! List<Object?>) {
      throw const RequestFailure('استجابة القائمة غير صالحة.');
    }
    return rawItems
        .whereType<Map<Object?, Object?>>()
        .map((Map<Object?, Object?> item) => item.cast<String, Object?>())
        .toList(growable: false);
  }

  Map<String, Object?> _map(Object? value) {
    if (value is Map<Object?, Object?>) return value.cast<String, Object?>();
    throw const RequestFailure('استجابة لوحة التحكم غير صالحة.');
  }
}

class _CachedSnapshot {
  const _CachedSnapshot(this.value, this.createdAt);

  final DashboardSnapshot value;
  final DateTime createdAt;

  bool get isExpired => DateTime.timestamp().difference(createdAt) >= LaravelDashboardRepository._cacheLifetime;
}
