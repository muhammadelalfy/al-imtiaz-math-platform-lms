# Optional Laravel Octane Runtime

## Decision

Laravel Octane is available as an **optional local or self-managed performance runtime**. The deployed LMS continues to use the existing **free Autoscale** HTTP path. Autoscale is request-scoped and may scale to zero; it must not depend on an Octane master process, a resident worker, or in-memory application state. This choice preserves the current no-always-on-worker operating model.

> Octane boots Laravel once and retains the application in memory for subsequent requests. It therefore requires explicit safeguards against stale request, container, configuration, and global state. [1]

| Concern                 | Current decision                           | Operational safeguard                                                                                                                               |
| ----------------------- | ------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Default production path | Free Autoscale                             | Keep the standard Laravel server entrypoint; do not configure Octane as the deployment command.                                                     |
| Optional local runtime  | FrankenPHP with Octane                     | Run `composer dev:octane` only on a developer-controlled machine or self-managed host.                                                              |
| Runtime binary          | Downloaded locally by the Octane installer | Ignore `frankenphp`, Caddy, and generated worker artifacts; do not commit or deploy them as project assets. [1]                                     |
| Long-lived worker state | Request state must not persist             | Keep request-aware services transient; do not inject `Request`, the container, authenticated users, or mutable arrays into singletons. [1]          |
| Worker health           | Recycling is bounded                       | The local script uses one worker and restarts it after 250 requests; Octane retains the 30-second maximum request execution time. [1]               |
| Queue processing        | No resident queue worker                   | Continue using the signed scheduled notification drain endpoint and the database queue. Octane is not a queue-worker substitute.                    |
| External delivery       | No provider bypass                         | WhatsApp and SMS credentials remain owner/administrator environment configuration; optional Octane changes no authorization or credential boundary. |

## Local execution

The installer generated `config/octane.php` and selected FrankenPHP as the default Octane server. The local command binds to loopback, uses a single worker by default, and accepts non-secret runtime overrides:

```bash
composer dev:octane
OCTANE_PORT=8080 OCTANE_WORKERS=2 OCTANE_MAX_REQUESTS=200 composer dev:octane
```

The first command expects the local FrankenPHP binary installed by `php artisan octane:install --server=frankenphp`. Laravel documents that this installer downloads the binary automatically for the FrankenPHP selection. [1] No `Dockerfile`, system service, process monitor, or hosting command is added by this project change.

Use `php artisan octane:reload` after deploying code to a self-managed Octane process. Long-lived workers do not automatically incorporate code, route, or configuration changes without a reload or restart. [1]

## State and dependency rules

The existing application keeps domain services bound through regular container bindings and handles authorization from the current request. Future additions must preserve that behavior. A singleton may contain immutable configuration or stateless collaborators, but it must not retain per-request data, a `Request` instance, an authenticated user, a mutable query builder, or a session-derived value.

| Safe pattern                                                                                  | Avoid                                                                                                |
| --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Resolve the current request or authenticated principal within the request handler.            | Capture a `Request`, `User`, or `Auth` value in a singleton constructor.                             |
| Use database/cache persistence for shared state and explicitly invalidate cache after writes. | Store learner, attendance, notification, or payment state in a PHP static property or worker memory. |
| Keep notification dispatch in queued jobs drained by the signed scheduled endpoint.           | Start a resident `queue:work` process merely because Octane is available.                            |
| Reload self-managed Octane workers after a release.                                           | Assume a running worker sees newly deployed PHP automatically.                                       |

Laravel's Octane documentation specifically warns against injecting the application container or HTTP request into constructors of long-lived bindings because they may be stale on later requests. [1]

## Hosting boundary

The optional runtime does **not** change the project's hosting recommendation. The current Autoscale deployment remains the supported release configuration and already supports the signed scheduled queue-drain request. A self-managed Octane deployment requires an operator to provide a supervised persistent process, TLS/static-asset handling, worker reloads during releases, logging, and health monitoring. Laravel’s own Octane production guidance describes using a process monitor to keep the long-lived server running. [1]

If a future deployment needs a permanent Octane process, it must be evaluated as a deliberate hosting change; it is not enabled by this configuration. The free Autoscale runtime should remain selected unless that operational requirement is explicitly accepted.

## Validation

The regression test `tests/Feature/OctaneConfigurationTest.php` confirms the FrankenPHP selection, request-reset listener configuration, worker-error handling, and 30-second execution guard. The full quality gate continues to run the Laravel suite, static analysis, frontend lint/typecheck/tests/build, Composer validation, documentation formatting, and whitespace checks.

## References

[1]: https://laravel.com/docs/13.x/octane "Laravel 13.x — Octane"
[2]: https://frankenphp.dev/docs/laravel/ "FrankenPHP — Laravel"
