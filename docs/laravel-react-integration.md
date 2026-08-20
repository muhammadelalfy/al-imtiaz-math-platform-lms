# Laravel-Root React Architecture

## Architecture

The LMS now uses **Laravel as the application host** and **React as Laravel’s Inertia frontend**. The original API contracts are preserved under `/api`, including Sanctum bearer-token authentication, while regular web URLs resolve through Laravel’s Inertia route into the React dashboard.

| Boundary          | Canonical location                                   | Responsibility                                                                                        |
| ----------------- | ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Laravel web shell | `routes/web.php` and `resources/views/app.blade.php` | Serves the Inertia page and React Vite assets for LMS routes.                                         |
| React application | `resources/js`                                       | Arabic RTL dashboard, exam authoring, rich media, PDF capture, theme system, and offline UI behavior. |
| Laravel API       | `routes/api.php`                                     | Existing Eloquent-backed LMS APIs and Sanctum authentication.                                         |
| Frontend build    | `vite.config.js`                                     | Laravel Vite plugin, React plugin, Tailwind, Sass processing, aliases, and `public/build` manifest.   |

## Local commands

From the repository root, install PHP packages with `composer install` and Node packages with `pnpm install`. After configuring the local Laravel environment and database, use `composer run dev` to start Laravel and the Vite server together. The equivalent Node command is `pnpm dev:full`.

Run the integration checks from the same directory:

```text
pnpm lint
pnpm check
pnpm test:frontend
pnpm build
php artisan test --compact
```

## Migration boundaries

The React application keeps its existing `/api` client and bearer-token contract, so role-specific admin, parent, and student login flows continue to use the same Laravel endpoints. The dedicated Inertia route is intentionally a thin host layer: it does not duplicate LMS data fetching, API authorization, or exam behavior. This keeps the transition low risk while moving the browser application into Laravel’s supported `resources/js` and Vite structure.

The former standalone React, Node/Express, Drizzle, and shared-contract workspaces were removed. New browser work belongs in `resources/js`; new backend work belongs in Laravel’s `app`, `routes`, `database`, `Modules`, and `tests` directories. The GitHub Actions **Frontend checks** job validates the Laravel-hosted React application from the repository root.
