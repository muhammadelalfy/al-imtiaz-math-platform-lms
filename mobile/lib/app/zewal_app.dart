import 'package:flutter/material.dart';

import '../core/ui/zewal_theme.dart';
import '../features/auth/presentation/auth_gate.dart';

class ZewalApp extends StatelessWidget {
  const ZewalApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'زويل التعليمية',
      theme: ZewalTheme.light,
      darkTheme: ZewalTheme.dark,
      themeMode: ThemeMode.system,
      home: const Directionality(
        textDirection: TextDirection.rtl,
        child: AuthGate(),
      ),
    );
  }
}
