# LMS

**Al-Imtiaz Math Platform** is an Arabic RTL learning-management system for mathematics education. The platform uses Laravel 13 as its API and domain backend with a standalone Next.js 16 frontend, Eloquent models, monitored exams, attendance QR workflows, reports, payments, worksheets, and dimensioned geometry questions.

## Architecture

| Area     | Stack and responsibility                                                                                                                                                     |
| -------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Frontend | Next.js 16 App Router, React 19, TypeScript, Tailwind CSS 4, Sass tokens, Arabic RTL UI, typed Laravel API client, IndexedDB offline sync, and browser-side exam PDF capture |
| Backend  | Laravel 13, PHP 8.3, Eloquent ORM, Sanctum role-specific authentication                                                                                                      |
| Modules  | `Modules/Attendance` and plugin-store boundaries using `nwidart/laravel-modules`                                                                                             |
| Data     | MySQL-compatible production configuration and SQLite test workflow                                                                                                           |
| Quality  | Next.js ESLint, Vitest + React Testing Library, Playwright, TypeScript checks, Laravel feature tests, PHP syntax checks, and production builds                               |

## Local development

The repository root remains the canonical Laravel application and API. The standalone Next.js frontend is located in `frontend/`; it preserves the existing Arabic RTL dashboard, role-specific accounts, typed `/api` client, offline synchronization, and all feature workspaces. For local development, Next.js proxies `/api/*` to Laravel at `LARAVEL_INTERNAL_ORIGIN`, which defaults to `http://127.0.0.1:5173`.

Install PHP dependencies with `composer install` from the repository root and Next.js dependencies with `pnpm --dir frontend install`. Configure the local Laravel environment, generate an application key, and apply migrations. Start Laravel with `php artisan serve --host 0.0.0.0 --port 5173`, then start Next.js with `pnpm --dir frontend dev`.

Run the Next.js checks with `pnpm --dir frontend lint`, `pnpm --dir frontend check`, `pnpm --dir frontend test`, `pnpm --dir frontend build`, and `pnpm --dir frontend test:e2e`. Run Laravel checks from the repository root with `php artisan test` and the PHP syntax command documented in `.github/workflows/ci.yml`.

See [`docs/laravel-api-architecture.md`](docs/laravel-api-architecture.md) for the repository contracts, cache policy, form-request validation, API-resource boundaries, and strict-loading rules used by the Laravel API.

See [`docs/plugin-payments.md`](docs/plugin-payments.md) for the plugin-store payment lifecycle, administrator approval controls, Egyptian payment-method configuration, and Stripe credential boundary.

See [`docs/multi-guard-authorization.md`](docs/multi-guard-authorization.md) for the separate account-login guards, staff role/permission CRUD controls, least-privilege boundaries, and verification workflow.

See [`docs/groups-notifications.md`](docs/groups-notifications.md) for academic-group CRUD, bulk student membership, group-targeted queued notifications, in-app inbox delivery, channel configuration, provider credential boundaries, and free scheduled queue processing.

See [`docs/offline-sync.md`](docs/offline-sync.md) for the first offline synchronization release: role-scoped IndexedDB snapshots, typed recorded-operation outbox, idempotent server reconciliation, conflict safeguards, retention limits, and reconnect behavior.

See [`docs/nextjs-frontend.md`](docs/nextjs-frontend.md) for the Next.js App Router architecture, Laravel API proxy, Arabic RTL routing, test stack, runtime configuration, and migration boundaries.

See [`docs/srs-ar.md`](docs/srs-ar.md) for the Arabic Software Requirements Specification, delivery status, diagrams, payment-security boundary, and future roadmap.

## Publishing

The selected production release model is **manual built-in publishing**. After a pull request has been merged and the required CI checks have passed, create or select the latest project checkpoint and use the **Publish** control in the project management interface. This model intentionally does not add a GitHub Actions deployment workflow, deploy-hook secret, SSH credential, or external hosting contract. GitHub Actions continues to validate code changes; the release decision remains an explicit project-owner action in the built-in publishing interface.

## Agile Git workflow

`main` is the protected, releasable branch. The long-lived integration branches are `backend` and `frontend`; each contains only changes for its corresponding application layer and is merged into `main` through review. Every feature starts from the relevant integration branch using a short-lived branch such as `feature/backend-exam-grading` or `feature/frontend-exam-preview`.

Pull Requests are required for all merges. A PR should describe the user story, acceptance criteria, implementation notes, tests, screenshots or PDF evidence when relevant, and any migration or rollback considerations. CI must pass before review. The preferred delivery cycle is: backlog item, feature branch, small commits, PR, automated checks, reviewer approval, squash merge, and branch deletion.

## Branch convention

| Branch               | Purpose                                                        |
| -------------------- | -------------------------------------------------------------- |
| `main`               | Protected release branch                                       |
| `backend`            | Laravel, Eloquent, migrations, modules, API, and backend tests |
| `frontend`           | Next.js, styling, client API integration, and frontend tests   |
| `feature/backend-*`  | Short-lived backend feature branch                             |
| `feature/frontend-*` | Short-lived frontend feature branch                            |
| `bugfix/*`           | Reproducible defect fix                                        |
| `chore/*`            | Tooling, documentation, or maintenance                         |

See [`docs/git-agile-workflow.md`](docs/git-agile-workflow.md) and the PR template for the complete process. CI runs on every push and Pull Request through `.github/workflows/ci.yml`.
