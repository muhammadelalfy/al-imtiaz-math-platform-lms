import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:zewal_mobile/app/providers.dart';
import 'package:zewal_mobile/app/zewal_app.dart';
import 'package:zewal_mobile/features/auth/domain/entities/app_user.dart';
import 'package:zewal_mobile/features/auth/domain/repositories/auth_repository.dart';

void main() {
  testWidgets('shows the Arabic login entry when no secure session exists', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authRepositoryProvider.overrideWithValue(_UnauthenticatedRepository()),
        ],
        child: const ZewalApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('زويل التعليمية'), findsOneWidget);
    expect(find.text('دخول'), findsOneWidget);
  });
}

class _UnauthenticatedRepository implements AuthRepository {
  @override
  Future<AppUser?> restoreSession() async => null;

  @override
  Future<AppUser> signIn({
    required UserRole role,
    required String email,
    required String password,
  }) {
    throw UnimplementedError();
  }

  @override
  Future<void> signOut() async {}
}
