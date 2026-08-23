import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/providers.dart';
import '../../dashboard/presentation/mobile_dashboard.dart';
import '../domain/entities/app_user.dart';
import 'login_screen.dart';

class AuthGate extends ConsumerWidget {
  const AuthGate({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final AsyncValue<AppUser?> session = ref.watch(sessionControllerProvider);
    return session.when(
      data: (AppUser? user) => user == null
          ? const LoginScreen()
          : MobileDashboard(user: user),
      error: (Object error, StackTrace stackTrace) => const LoginScreen(
        initialError: 'تعذر استعادة الجلسة. سجّل الدخول مرة أخرى.',
      ),
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator.adaptive()),
      ),
    );
  }
}
