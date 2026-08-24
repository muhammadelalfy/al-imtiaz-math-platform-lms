import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/network/laravel_api_client.dart';
import '../core/security/secure_session_store.dart';
import '../features/attendance/data/laravel_attendance_repository.dart';
import '../features/attendance/domain/repositories/attendance_repository.dart';
import '../features/auth/data/auth_remote_data_source.dart';
import '../features/auth/data/laravel_auth_repository.dart';
import '../features/auth/domain/entities/app_user.dart';
import '../features/auth/domain/repositories/auth_repository.dart';
import '../features/dashboard/data/laravel_dashboard_repository.dart';
import '../features/dashboard/domain/entities/dashboard_snapshot.dart';
import '../features/dashboard/domain/repositories/dashboard_repository.dart';

final Provider<FlutterSecureStorage> secureStorageProvider =
    Provider<FlutterSecureStorage>((Ref ref) => const FlutterSecureStorage());

final Provider<SecureSessionStore> secureSessionStoreProvider =
    Provider<SecureSessionStore>(
      (Ref ref) => SecureSessionStore(ref.watch(secureStorageProvider)),
    );

final Provider<LaravelApiClient> laravelApiClientProvider =
    Provider<LaravelApiClient>(
  (Ref ref) => LaravelApiClient(ref.watch(secureSessionStoreProvider)),
    );

final Provider<AuthRemoteDataSource> authRemoteDataSourceProvider =
    Provider<AuthRemoteDataSource>(
      (Ref ref) => AuthRemoteDataSource(ref.watch(laravelApiClientProvider)),
    );

final Provider<AuthRepository> authRepositoryProvider = Provider<AuthRepository>(
  (Ref ref) => LaravelAuthRepository(
    ref.watch(authRemoteDataSourceProvider),
    ref.watch(secureSessionStoreProvider),
  ),
);

final Provider<DashboardRepository> dashboardRepositoryProvider =
    Provider<DashboardRepository>(
      (Ref ref) => LaravelDashboardRepository(
        ref.watch(laravelApiClientProvider),
      ),
    );

final Provider<AttendanceRepository> attendanceRepositoryProvider =
    Provider<AttendanceRepository>(
      (Ref ref) => LaravelAttendanceRepository(
        ref.watch(laravelApiClientProvider),
      ),
    );

final dashboardSnapshotProvider = FutureProvider.autoDispose
    .family<DashboardSnapshot, DashboardScope>(
      (Ref ref, DashboardScope scope) => ref
          .watch(dashboardRepositoryProvider)
          .loadSnapshot(
            userId: scope.userId,
            includePlatformTelemetry: scope.includePlatformTelemetry,
          ),
    );

final AsyncNotifierProvider<SessionController, AppUser?>
sessionControllerProvider = AsyncNotifierProvider<SessionController, AppUser?>(
  SessionController.new,
);

class SessionController extends AsyncNotifier<AppUser?> {
  @override
  Future<AppUser?> build() => ref.read(authRepositoryProvider).restoreSession();

  Future<void> signIn({
    required UserRole role,
    required String email,
    required String password,
  }) async {
    state = const AsyncLoading<AppUser?>();
    state = await AsyncValue.guard(
      () => ref
          .read(authRepositoryProvider)
          .signIn(role: role, email: email, password: password),
    );
  }

  Future<void> signOut() async {
    await ref.read(authRepositoryProvider).signOut();
    state = const AsyncData<AppUser?>(null);
  }
}

class DashboardScope {
  const DashboardScope({
    required this.userId,
    required this.includePlatformTelemetry,
  });

  final int userId;
  final bool includePlatformTelemetry;

  @override
  bool operator ==(Object other) {
    return other is DashboardScope &&
        other.userId == userId &&
        other.includePlatformTelemetry == includePlatformTelemetry;
  }

  @override
  int get hashCode => Object.hash(userId, includePlatformTelemetry);
}
