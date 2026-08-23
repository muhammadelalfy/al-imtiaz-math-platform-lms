# Zewal Flutter Companion Architecture

## Scope

The Flutter application is a native companion to the Laravel-powered Zewal platform. It consumes the existing authenticated API and deliberately does not reproduce authorization, tenant isolation, subscription activation, payment approval, or academic business rules on the device.

## Layered Structure

```text
lib/
  app/                         # Composition root, router, theme, app bootstrap
  core/
    config/                    # Compile-time API configuration
    errors/                    # Typed failures and error mapping
    network/                   # HTTP client, authorization interceptor, retry policy
    security/                  # Secure token/session persistence
    ui/                        # Shared RTL widgets and design tokens
  features/
    auth/
      data/ domain/ presentation/
    dashboard/
      data/ domain/ presentation/
    students/
      data/ domain/ presentation/
    attendance/
      data/ domain/ presentation/
    learning/
      data/ domain/ presentation/
    notifications/
      data/ domain/ presentation/
    platform/
      data/ domain/ presentation/
```

Each feature owns its entity models, repository interface, data-source implementation, use cases, providers, and screens. The presentation layer depends only on use cases; it never constructs requests, reads secure storage, or parses API payloads directly. Concrete repositories are assembled once in the application composition root.

## Design Principles

The architecture follows **SOLID** boundaries. Domain repositories are small, capability-specific contracts; data sources implement those contracts with the Laravel API; and presentation state depends on abstractions injected through Riverpod providers. Immutable value objects, `const` widgets, bounded list rendering, and request cancellation prevent unnecessary allocations and rebuilds.

API failures map into typed `AppFailure` values. A session interceptor attaches the Sanctum bearer token only to first-party requests, and the token is persisted exclusively through the platform secure-storage implementation. Sign-out always removes the local token before returning the user to the login route.

## Performance Contract

| Area | Rule |
| --- | --- |
| Rendering | Use `ListView.builder` / `CustomScrollView` for variable collections; avoid unbounded `Column.map` list rendering. |
| State | Keep state feature-scoped with selective Riverpod reads; do not expose mutable API maps to widgets. |
| Networking | Reuse one configured `Dio` client, apply a small retry policy only to transient idempotent requests, and retain decoded DTOs behind repositories. |
| Storage | Persist only encrypted session credentials and bounded, non-sensitive read caches. |
| Media and QR | Defer camera/QR code activation until the attendance route is visible. |
| Observability | Attach only request duration, status, and route metadata to diagnostics; never persist learner content or tokens in logs. |

## Role and Route Policy

The mobile app supports teacher, parent, student, and super-admin sessions. The `/control/login` equivalent is not presented as a public option. A super-admin may authenticate through the admin API route only when the API returns `is_super_admin: true`; otherwise the local session is cleared immediately.

## First Mobile Release

The first mobile release includes secure role login, dashboard summary, student list, attendance/QR entry point, worksheets, exam results, payments, notification inbox, and the super-admin platform health view. Editing and payment approval flows remain server-authorized and are exposed only where the existing API and role claims allow them.
