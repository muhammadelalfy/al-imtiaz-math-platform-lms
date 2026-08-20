# Next.js Frontend Migration

## Purpose and boundary

The active web frontend now lives in [`frontend/`](../frontend) as a **Next.js 16.3.1 App Router** application. Laravel remains the authoritative domain and API backend. This separation removes the runtime dependency on an Inertia-hosted client while retaining the validated Laravel models, Form Requests, API Resources, Sanctum multi-guard endpoints, offline synchronization service, and authorization rules.

> The migration deliberately retains the existing client feature source inside `frontend/src/lms` while feature delivery is stabilized under the new App Router. The former `resources/js` tree is a migration source and rollback reference; it is not the active Next.js runtime entrypoint.

| Concern               | Next.js frontend responsibility                                                                                                          | Laravel responsibility                                                                  |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Routing and rendering | App Router pages for `/`, `/admin/login`, `/parent/login`, and `/student/login`; Arabic RTL root layout; client-only LMS dashboard shell | API routes and business rules under `/api`                                              |
| Authentication        | Role-appropriate portal selection; token handling in the typed client                                                                    | Separate Sanctum admin, teacher, parent, and student guard login endpoints              |
| Feature workspaces    | Students, attendance, QR scanning, exams, worksheets, payments, reports, plugins, groups, notifications, and authorization screens       | Eloquent persistence, policy/permission enforcement, queued work, and API resources     |
| Offline operations    | Role-scoped IndexedDB snapshot and typed outbox with reconnect/manual replay feedback                                                    | Scoped snapshot endpoint, idempotency records, conflict protection, and operation audit |

## Runtime configuration

The typed client keeps using relative `/api` requests. The Next.js configuration proxies this route server-side to `LARAVEL_INTERNAL_ORIGIN`, defaulting to `http://127.0.0.1:5173` for local development. This retains a same-origin browser contract without exposing the Laravel internal address to the client. Set `LARAVEL_INTERNAL_ORIGIN` only when a non-default local Laravel origin is required; never place payment or provider secrets in a `NEXT_PUBLIC_*` variable.

The root layout sets `lang="ar"` and `dir="rtl"`. The dashboard is loaded through a client-only route boundary because the preserved LMS includes intentionally browser-specific capabilities such as local storage, IndexedDB, QR/camera access, rich text editing, MathLive, print capture, and monitored-exam UI behavior.

## Local development and tests

Run Laravel on port `5173`, then start Next.js on its default port from the `frontend` directory. The browser route works against the API proxy without any client-side CORS configuration.

| Command                        | Validation purpose                                               |
| ------------------------------ | ---------------------------------------------------------------- |
| `pnpm --dir frontend lint`     | App Router and standalone test linting                           |
| `pnpm --dir frontend check`    | TypeScript type safety                                           |
| `pnpm --dir frontend test`     | Vitest and React Testing Library unit coverage                   |
| `pnpm --dir frontend build`    | Production Next.js build using the Webpack fallback              |
| `pnpm --dir frontend test:e2e` | Playwright login-surface validation with system Chromium locally |

The production build explicitly uses Webpack because the first Turbopack build of the large migrated editor bundle aborted in the current sandbox. Turbopack remains enabled for local development. This is a reproducible build compatibility decision rather than a feature limitation.

Next.js documents the App Router and TypeScript-first setup used here, including its default TypeScript, ESLint, Tailwind, and App Router scaffold.[1] The unit stack follows Next.js guidance for Vitest with React Testing Library, while browser behavior is covered by Playwright as recommended for end-to-end flows.[2] [3]

## CI and deployment boundary

The **Frontend checks** GitHub Actions job honors the repository-pinned pnpm version, installs `frontend/pnpm-lock.yaml`, runs linting, TypeScript, Vitest, production build, installs Playwright Chromium, and executes the login E2E test. Laravel checks remain a separate required job.

The current managed production path must run the standalone Next.js server and Laravel API behind the same public origin (or provide a reverse proxy for `/api/*`). This is a deployment concern, not an application-level CORS workaround. The free Autoscale queue-drain approach remains unchanged because it is invoked through Laravel’s signed scheduled endpoint, not an always-on worker.

## References

[1]: https://nextjs.org/docs/app/getting-started/installation "Next.js Installation"
[2]: https://nextjs.org/docs/app/guides/testing/vitest "Next.js Vitest Guide"
[3]: https://nextjs.org/docs/pages/guides/testing/playwright "Next.js Playwright Guide"
