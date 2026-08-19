# Continuous Integration

The repository CI workflow runs on every push and pull request through GitHub Actions. It uses an Ubuntu runner with **Node.js 22**, **pnpm 10.4.1**, **PHP 8.3**, Composer 2, and the PHP extensions required by Laravel, SQLite tests, and ZIP-based plugin installation.

The frontend job installs dependencies with `pnpm install --frozen-lockfile`, then runs the scoped `pnpm lint` script, TypeScript checks, Vitest, and the production build. The lint scope intentionally covers maintained application-client/API files and CI documentation/configuration. Generated shadcn components and legacy template files are not included in the gate until they are normalized in a dedicated formatting pass.

The Laravel job installs from `composer.lock`, copies `.env.example`, generates an application key, creates a SQLite database file, sets `DB_CONNECTION=sqlite` and `DB_DATABASE`, runs migrations, checks PHP syntax with `php -l`, and executes the Laravel feature suite. The PHP lint step is syntax validation rather than a style formatter, keeping CI dependency-light and compatible with the existing Laravel codebase.

To reproduce the CI checks locally from the repository root, run:

```bash
pnpm install --frozen-lockfile
pnpm lint
pnpm check
pnpm test -- --run
pnpm build
composer install --no-interaction --prefer-dist --no-progress
mkdir -p database
touch database/database.sqlite
cat > .env <<EOF
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=$PWD/database/database.sqlite
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
EOF
php artisan key:generate
php artisan migrate --force
find app Modules tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php artisan test --compact
```
