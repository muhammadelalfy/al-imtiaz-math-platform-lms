import '../../../core/errors/app_failure.dart';
import '../../../core/network/laravel_api_client.dart';
import '../domain/entities/app_user.dart';

class AuthRemoteDataSource {
  const AuthRemoteDataSource(this._client);

  final LaravelApiClient _client;

  Future<AuthSession> signIn({
    required UserRole role,
    required String email,
    required String password,
  }) async {
    final Object? body = await _client.post(
      '/auth/${role.apiValue}/login',
      data: <String, Object?>{'email': email, 'password': password},
    );
    final Map<String, Object?> map = _asMap(body);
    final Object? rawUser = map['user'];
    if (rawUser is! Map<Object?, Object?> || map['token'] is! String) {
      throw const RequestFailure('استجابة تسجيل الدخول غير صالحة.');
    }
    return AuthSession(
      user: AppUser.fromJson(rawUser.cast<String, Object?>()),
      token: map['token'] as String,
    );
  }

  Future<AppUser> currentUser() async {
    final Object? body = await _client.get('/auth/me');
    return AppUser.fromJson(_asMap(body));
  }

  Future<void> signOut() async {
    await _client.post('/auth/logout');
  }

  Map<String, Object?> _asMap(Object? value) {
    if (value is Map<Object?, Object?>) return value.cast<String, Object?>();
    throw const RequestFailure('استجابة الخادم غير صالحة.');
  }
}
