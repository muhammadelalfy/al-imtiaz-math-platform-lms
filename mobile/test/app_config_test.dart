import 'package:flutter_test/flutter_test.dart';
import 'package:zewal_mobile/core/config/app_config.dart';

void main() {
  group('AppConfig.resolveApiUrl', () {
    test('uses the selected non-production environment URL', () {
      final String value = AppConfig.resolveApiUrl(
        environment: AppEnvironment.staging,
        overrideApiUrl: '',
        developmentApiUrl: 'http://10.0.2.2:8000/api/',
        stagingApiUrl: 'https://staging.zewal.example/api/',
        productionApiUrl: 'https://app.zewal.example/api/',
      );

      expect(value, 'https://staging.zewal.example/api');
    });

    test('refuses an insecure production URL', () {
      final String value = AppConfig.resolveApiUrl(
        environment: AppEnvironment.production,
        overrideApiUrl: '',
        developmentApiUrl: '',
        stagingApiUrl: '',
        productionApiUrl: 'http://app.zewal.example/api',
      );

      expect(value, isEmpty);
    });

    test('allows a one-off valid API override for preview builds', () {
      final String value = AppConfig.resolveApiUrl(
        environment: AppEnvironment.development,
        overrideApiUrl: 'https://preview.zewal.example/api/',
        developmentApiUrl: '',
        stagingApiUrl: '',
        productionApiUrl: '',
      );

      expect(value, 'https://preview.zewal.example/api');
    });
  });
}
