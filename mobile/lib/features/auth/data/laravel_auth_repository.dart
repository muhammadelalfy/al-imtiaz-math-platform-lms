import '../../../core/security/secure_session_store.dart';
import '../domain/entities/app_user.dart';
import '../domain/repositories/auth_repository.dart';
import 'auth_remote_data_source.dart';

class LaravelAuthRepository implements AuthRepository {
  const LaravelAuthRepository(this._remote, this._sessionStore);

  final AuthRemoteDataSource _remote;
  final SecureSessionStore _sessionStore;

  @override
  Future<AppUser?> restoreSession() async {
    final String? token = await _sessionStore.readToken();
    if (token == null || token.isEmpty) return null;
    try {
      return await _remote.currentUser();
    } catch (_) {
      await _sessionStore.clear();
      return null;
    }
  }

  @override
  Future<AppUser> signIn({
    required UserRole role,
    required String email,
    required String password,
  }) async {
    final AuthSession session = await _remote.signIn(
      role: role,
      email: email,
      password: password,
    );
    await _sessionStore.saveToken(session.token);
    return session.user;
  }

  @override
  Future<void> signOut() async {
    try {
      await _remote.signOut();
    } finally {
      await _sessionStore.clear();
    }
  }
}
