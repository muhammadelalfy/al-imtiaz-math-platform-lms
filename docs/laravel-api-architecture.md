# Laravel API Architecture

## Purpose

The LMS API uses thin controllers, **role-aware form requests**, stable **JSON resources**, and injected repository contracts. This keeps HTTP validation, persistence queries, cache concerns, and response formatting independently changeable while retaining the existing `/api` payload shapes used by the React application.

| Boundary                  | Implementation               | Responsibility                                                                                     |
| ------------------------- | ---------------------------- | -------------------------------------------------------------------------------------------------- |
| Controller                | `app/Http/Controllers/Api`   | Authorization hand-off, HTTP status selection, and request-to-use-case coordination.               |
| Form request              | `app/Http/Requests`          | Input validation and staff authorization for student, attendance, payment, and exam-result writes. |
| Repository contract       | `app/Contracts/Repositories` | Narrow abstractions for student persistence and dashboard metrics reads.                           |
| Repository implementation | `app/Repositories`           | Eloquent query shape, explicit relationship loading, and dashboard metric caching.                 |
| API resource              | `app/Http/Resources`         | Stable client-safe output for core LMS records and report summaries.                               |

## Cache policy

`CachedDashboardMetricsRepository` caches only the aggregate dashboard/report summary under `lms:dashboard-metrics:v1` for five minutes. It is invalidated after a student, attendance, exam-result, or payment write. This targets the aggregate endpoint that fans out over multiple tables without caching mutable student, attendance, payment, or exam lists that are actively edited in the dashboard.

> Cache invalidation belongs at the write boundary. Attendance invalidates through its domain service; student, payment, and exam-result writes invalidate through their controller/use-case boundary.

## Loading discipline

`AppServiceProvider` enables `Model::preventLazyLoading()` globally. Repository queries and controller ownership checks therefore use `with`, `load`, or `loadMissing` deliberately. New code must declare every API relation it reads rather than relying on an implicit query after serialization or ownership logic starts.

## Extension rules

New CRUD work should first add a narrowly scoped repository contract when it needs a persistence seam or specialized query policy. Use a form request for role-aware validation, a resource whenever persistence data crosses the API boundary, and explicit cache invalidation only for cached read models affected by the write. Avoid creating a generic repository that exposes unused operations.
