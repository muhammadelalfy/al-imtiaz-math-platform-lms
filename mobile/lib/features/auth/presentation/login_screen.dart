import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/providers.dart';
import '../../../core/config/app_config.dart';
import '../../../core/errors/app_failure.dart';
import '../domain/entities/app_user.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key, this.initialError});

  final String? initialError;

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  UserRole _role = UserRole.teacher;
  String? _error;

  @override
  void initState() {
    super.initState();
    _error = widget.initialError;
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _error = null);
    try {
      await ref
          .read(sessionControllerProvider.notifier)
          .signIn(
            role: _role,
            email: _emailController.text.trim(),
            password: _passwordController.text,
          );
    } on AppFailure catch (failure) {
      if (mounted) setState(() => _error = failure.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'تعذر تسجيل الدخول حالياً.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final AsyncValue session = ref.watch(sessionControllerProvider);
    final bool isBusy = session.isLoading;
    final ColorScheme colors = Theme.of(context).colorScheme;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: <Widget>[
                        Icon(
                          Icons.auto_awesome_rounded,
                          color: colors.primary,
                          size: 38,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'زويل التعليمية',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'تسجيل دخول آمن إلى أدواتك التعليمية',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                        const SizedBox(height: 24),
                        if (!AppConfig.isApiConfigured)
                          _Notice(
                            message: 'شغّل التطبيق مع رابط واجهة Laravel البرمجية أولاً.',
                            color: colors.tertiary,
                          ),
                        if (_error != null)
                          _Notice(message: _error!, color: colors.error),
                        SegmentedButton<UserRole>(
                          segments: UserRole.values
                              .map(
                                (UserRole role) => ButtonSegment<UserRole>(
                                  value: role,
                                  label: Text(role.label),
                                ),
                              )
                              .toList(growable: false),
                          selected: <UserRole>{_role},
                          showSelectedIcon: false,
                          onSelectionChanged: isBusy
                              ? null
                              : (Set<UserRole> selection) {
                                  setState(() => _role = selection.first);
                                },
                        ),
                        const SizedBox(height: 20),
                        TextFormField(
                          controller: _emailController,
                          enabled: !isBusy,
                          keyboardType: TextInputType.emailAddress,
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(
                            labelText: 'البريد الإلكتروني',
                            prefixIcon: Icon(Icons.email_outlined),
                          ),
                          validator: (String? value) =>
                              value != null && value.contains('@')
                              ? null
                              : 'أدخل بريداً إلكترونياً صحيحاً.',
                        ),
                        const SizedBox(height: 14),
                        TextFormField(
                          controller: _passwordController,
                          enabled: !isBusy,
                          obscureText: true,
                          textInputAction: TextInputAction.done,
                          onFieldSubmitted: (_) => _submit(),
                          decoration: const InputDecoration(
                            labelText: 'كلمة المرور',
                            prefixIcon: Icon(Icons.lock_outline),
                          ),
                          validator: (String? value) =>
                              value != null && value.isNotEmpty
                              ? null
                              : 'أدخل كلمة المرور.',
                        ),
                        const SizedBox(height: 22),
                        FilledButton.icon(
                          onPressed: isBusy || !AppConfig.isApiConfigured
                              ? null
                              : _submit,
                          icon: isBusy
                              ? const SizedBox.square(
                                  dimension: 18,
                                  child: CircularProgressIndicator.adaptive(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.login_rounded),
                          label: Text(isBusy ? 'جارٍ تسجيل الدخول...' : 'دخول'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Notice extends StatelessWidget {
  const _Notice({required this.message, required this.color});

  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(message, style: TextStyle(color: color)),
    );
  }
}
