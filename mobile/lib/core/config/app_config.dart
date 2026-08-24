enum AppEnvironment {
  development,
  staging,
  production;

  static AppEnvironment parse(String value) =>
      switch (value.trim().toLowerCase()) {
        'production' => AppEnvironment.production,
        'staging' => AppEnvironment.staging,
        _ => AppEnvironment.development,
      };
}

class AppConfig {
  const AppConfig._();

  static const String _selectedEnvironment = String.fromEnvironment(
    'ZEWAL_ENV',
    defaultValue: 'development',
  );
  static const String _overrideApiUrl = String.fromEnvironment('ZEWAL_API_URL');
  static const String _developmentApiUrl = String.fromEnvironment(
    'ZEWAL_DEVELOPMENT_API_URL',
  );
  static const String _stagingApiUrl = String.fromEnvironment(
    'ZEWAL_STAGING_API_URL',
  );
  static const String _productionApiUrl = String.fromEnvironment(
    'ZEWAL_PRODUCTION_API_URL',
  );

  static AppEnvironment get environment =>
      AppEnvironment.parse(_selectedEnvironment);

  static String get apiBaseUrl => resolveApiUrl(
    environment: environment,
    overrideApiUrl: _overrideApiUrl,
    developmentApiUrl: _developmentApiUrl,
    stagingApiUrl: _stagingApiUrl,
    productionApiUrl: _productionApiUrl,
  );

  static String resolveApiUrl({
    required AppEnvironment environment,
    required String overrideApiUrl,
    required String developmentApiUrl,
    required String stagingApiUrl,
    required String productionApiUrl,
  }) {
    final String configured = overrideApiUrl.trim().isNotEmpty
        ? overrideApiUrl
        : switch (environment) {
            AppEnvironment.development => developmentApiUrl,
            AppEnvironment.staging => stagingApiUrl,
            AppEnvironment.production => productionApiUrl,
          };
    final String normalized = configured.trim().replaceFirst(
      RegExp(r'/+$'),
      '',
    );
    final Uri? uri = Uri.tryParse(normalized);
    if (uri == null || !uri.hasScheme || uri.host.isEmpty) return '';
    if (environment == AppEnvironment.production && uri.scheme != 'https') {
      return '';
    }
    return normalized;
  }

  static bool get isApiConfigured => apiBaseUrl.isNotEmpty;
}
