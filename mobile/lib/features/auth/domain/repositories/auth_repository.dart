import '../entities/app_user.dart';

abstract interface class AuthRepository {
  Future<AppUser?> restoreSession();

  Future<AppUser> signIn({
    required UserRole role,
    required String email,
    required String password,
  });

  Future<void> signOut();
}
