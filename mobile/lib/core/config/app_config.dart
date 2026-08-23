class AppConfig {
  const AppConfig._();

  static const String apiBaseUrl = String.fromEnvironment(
    'ZEWAL_API_URL',
    defaultValue: '',
  );

  static bool get isApiConfigured => apiBaseUrl.trim().isNotEmpty;
}
