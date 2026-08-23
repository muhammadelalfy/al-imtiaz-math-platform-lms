import 'package:dio/dio.dart';

sealed class AppFailure implements Exception {
  const AppFailure(this.message, {this.isRetryable = false});

  factory AppFailure.fromDio(DioException exception) {
    final int? statusCode = exception.response?.statusCode;
    final Object? body = exception.response?.data;
    final String? apiMessage = body is Map<Object?, Object?>
        ? body['message'] as String?
        : null;

    if (statusCode == 401) return const UnauthorizedFailure();
    if (statusCode != null && statusCode >= 500) {
      return ServerFailure(apiMessage ?? 'تعذر إتمام الطلب من الخادم.');
    }
    if (exception.type == DioExceptionType.connectionError ||
        exception.type == DioExceptionType.connectionTimeout ||
        exception.type == DioExceptionType.receiveTimeout) {
      return const NetworkFailure();
    }
    return RequestFailure(apiMessage ?? 'تعذر إتمام الطلب.');
  }

  final String message;
  final bool isRetryable;
}

final class ConfigurationFailure extends AppFailure {
  const ConfigurationFailure()
    : super(
        'لم يتم ضبط رابط واجهة زويل البرمجية. شغّل التطبيق مع ZEWAL_API_URL.',
      );
}

final class NetworkFailure extends AppFailure {
  const NetworkFailure()
    : super('تعذر الاتصال بالخادم. تحقق من اتصال الإنترنت.', isRetryable: true);
}

final class RequestFailure extends AppFailure {
  const RequestFailure(super.message);
}

final class ServerFailure extends AppFailure {
  const ServerFailure(super.message) : super(isRetryable: true);
}

final class UnauthorizedFailure extends AppFailure {
  const UnauthorizedFailure() : super('انتهت الجلسة. سجّل الدخول مرة أخرى.');
}
