import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../errors/app_failure.dart';
import '../security/secure_session_store.dart';

class LaravelApiClient {
  LaravelApiClient(this._sessionStore, {Dio? dio})
    : _dio =
          dio ??
          Dio(
            BaseOptions(
              baseUrl: AppConfig.apiBaseUrl,
              connectTimeout: const Duration(seconds: 10),
              receiveTimeout: const Duration(seconds: 15),
              sendTimeout: const Duration(seconds: 15),
              headers: const <String, Object>{
                Headers.acceptHeader: Headers.jsonContentType,
                Headers.contentTypeHeader: Headers.jsonContentType,
              },
            ),
          ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest:
            (RequestOptions options, RequestInterceptorHandler handler) async {
              final String? token = await _sessionStore.readToken();
              if (token != null && token.isNotEmpty) {
                options.headers['Authorization'] = 'Bearer $token';
              }
              handler.next(options);
            },
      ),
    );
  }

  final Dio _dio;
  final SecureSessionStore _sessionStore;

  Future<Object?> get(String path) => _request('GET', path);

  Future<Object?> post(String path, {Map<String, Object?>? data}) {
    return _request('POST', path, data: data);
  }

  Future<Object?> _request(
    String method,
    String path, {
    Map<String, Object?>? data,
  }) async {
    if (!AppConfig.isApiConfigured) throw const ConfigurationFailure();

    for (var attempt = 0; attempt < 2; attempt++) {
      try {
        final Response<Object?> response = await _dio.request<Object?>(
          path,
          data: data,
          options: Options(method: method),
        );
        return response.data;
      } on DioException catch (exception) {
        final AppFailure failure = AppFailure.fromDio(exception);
        final bool canRetry =
            method == 'GET' && failure.isRetryable && attempt == 0;
        if (!canRetry) throw failure;
        await Future<void>.delayed(const Duration(milliseconds: 280));
      }
    }
    throw const NetworkFailure();
  }
}
