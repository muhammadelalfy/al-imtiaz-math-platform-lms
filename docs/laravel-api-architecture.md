# Laravel API Architecture

## Purpose

The LMS API uses thin controllers, **role-aware form requests**, stable **JSON resources**, and injected repository contracts. This keeps HTTP validation, persistence queries, cache concerns, and response formatting independently changeable while retaining the existing `/api` payload shapes used by the React application.

| Boundary                  | Implementation               | Responsibility                                                                                                               |
| ------------------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Controller                | `app/Http/Controllers/Api`   | Authorization hand-off, HTTP status selection, and request-to-use-case coordination.                                         |
| Form request              | `app/Http/Requests`          | Input validation and staff authorization for student, attendance, payment, exam-result, worksheet, and exam-template writes. |
| Repository contract       | `app/Contracts/Repositories` | Narrow abstractions for student, worksheet, exam-template, question-bank, plugin-store, and dashboard metrics reads.         |
| Repository implementation | `app/Repositories`           | Eloquent query shape, explicit relationship loading, and dashboard metric caching.                                           |
| API resource              | `app/Http/Resources`         | Stable client-safe output for core LMS records and report summaries.                                                         |

## Cache policy

`CachedDashboardMetricsRepository` caches only the aggregate dashboard/report summary under `lms:dashboard-metrics:v1` for five minutes. It is invalidated after a student, attendance, exam-result, or payment write. This targets the aggregate endpoint that fans out over multiple tables without caching mutable student, attendance, payment, or exam lists that are actively edited in the dashboard.

> Cache invalidation belongs at the write boundary. Attendance invalidates through its domain service; student, payment, and exam-result writes invalidate through their controller/use-case boundary.

### Cache observability

`LogCacheObservability` records a `hit` or `miss` for the `dashboard-metrics` cache name and maintains daily aggregate counters. The telemetry deliberately contains no user ID, student ID, request payload, query term, or cached value. Inspect the counter snapshot through the injected `CacheObservabilityInterface`, or collect the structured `cache.telemetry` application log events with the host’s log pipeline.

## Static analysis

Larastan runs at level 5 through `composer analyse`, using `phpstan.neon` and the Laravel container-aware extension. The **Laravel checks** CI job runs this command after migrations and before feature tests. The project currently uses no analysis baseline or ignored errors: new and existing application findings fail the CI job, so contracts, dynamic Eloquent relations, resources, and query boundaries must remain analyzable.

## Loading discipline

`AppServiceProvider` enables `Model::preventLazyLoading()` globally. Repository queries and controller ownership checks therefore use `with`, `load`, or `loadMissing` deliberately. New code must declare every API relation it reads rather than relying on an implicit query after serialization or ownership logic starts.

## Extension rules

New CRUD work should first add a narrowly scoped repository contract when it needs a persistence seam or specialized query policy. Use a form request for role-aware validation, a resource whenever persistence data crosses the API boundary, and explicit cache invalidation only for cached read models affected by the write. Worksheet and exam-template repositories provide the current reference implementations for relationship-aware reads and transactional child-record synchronization. The question-bank repository owns filtered, department-loaded authoring queries; the plugin-store repository owns active catalog, per-user purchase entitlement, and installation-state reads. Avoid creating a generic repository that exposes unused operations.
