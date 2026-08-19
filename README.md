# LMS

**Al-Imtiaz Math Platform** is an Arabic RTL learning-management system for mathematics education. The platform is a Laravel 13 application with an Inertia-hosted React 19 frontend, Eloquent models, monitored exams, attendance QR workflows, reports, payments, worksheets, and dimensioned geometry questions.

## Architecture

| Area     | Stack and responsibility                                                                                                     |
| -------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Frontend | React 19, TypeScript, Inertia 2, Laravel Vite, Tailwind CSS 4, Sass tokens, Arabic RTL UI, and browser-side exam PDF capture |
| Backend  | Laravel 13, PHP 8.3, Eloquent ORM, Sanctum role-specific authentication                                                      |
| Modules  | `Modules/Attendance` and plugin-store boundaries using `nwidart/laravel-modules`                                             |
| Data     | MySQL-compatible production configuration and SQLite test workflow                                                           |
| Quality  | Vitest, TypeScript checks, Laravel feature tests, PHP syntax checks, and Vite production builds                              |

## Local development

The repository root is the canonical Laravel application. The React source is located in `resources/js`, Laravel serves the Inertia shell through `routes/web.php`, and the existing Sanctum API continues to be available under `/api`.

Install PHP dependencies with `composer install` and frontend dependencies with `pnpm install` from the repository root. Configure the local Laravel environment, generate an application key, and apply migrations. Start Laravel and the React Vite server together with `composer run dev`, or run `pnpm dev:full`.

Run the React checks from the repository root with `pnpm lint`, `pnpm check`, `pnpm test:frontend`, and `pnpm build`. Run Laravel checks from the same directory with `php artisan test` and the PHP syntax command documented in `.github/workflows/ci.yml`.

## Agile Git workflow

`main` is the protected, releasable branch. The long-lived integration branches are `backend` and `frontend`; each contains only changes for its corresponding application layer and is merged into `main` through review. Every feature starts from the relevant integration branch using a short-lived branch such as `feature/backend-exam-grading` or `feature/frontend-exam-preview`.

Pull Requests are required for all merges. A PR should describe the user story, acceptance criteria, implementation notes, tests, screenshots or PDF evidence when relevant, and any migration or rollback considerations. CI must pass before review. The preferred delivery cycle is: backlog item, feature branch, small commits, PR, automated checks, reviewer approval, squash merge, and branch deletion.

## Branch convention

| Branch               | Purpose                                                        |
| -------------------- | -------------------------------------------------------------- |
| `main`               | Protected release branch                                       |
| `backend`            | Laravel, Eloquent, migrations, modules, API, and backend tests |
| `frontend`           | React, styling, client API integration, and frontend tests     |
| `feature/backend-*`  | Short-lived backend feature branch                             |
| `feature/frontend-*` | Short-lived frontend feature branch                            |
| `bugfix/*`           | Reproducible defect fix                                        |
| `chore/*`            | Tooling, documentation, or maintenance                         |

See [`docs/git-agile-workflow.md`](docs/git-agile-workflow.md) and the PR template for the complete process. CI runs on every push and Pull Request through `.github/workflows/ci.yml`.
