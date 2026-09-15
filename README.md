# MainPadel

MainPadel is a focused web utility for organizing rotating padel games. It helps an organizer create a Game, generate fair draws, play through rounds, enter scores quickly, and follow live individual standings.

The application is intentionally small and practical: server-rendered Laravel pages, lightweight Alpine.js interactions, and a MySQL database that can run on standard shared hosting.

## Product flow

```text
Create Game → Add Players → Generate Draw → Play → Enter Score
          → Next Round → Live Individual Standings
```

MainPadel supports:

- User registration, sign in, sign out, and per-user Game ownership.
- Mobile-first Game setup with players, courts, target points, and Auto or Custom rounds.
- Fair draw generation with hard-constraint validation and configurable fairness scoring.
- Sequential rounds with balanced matches, rests, partners, and opponents.
- Fast score entry with server-side validation.
- Derived individual standings during and after a Game.
- Late joins, temporary player pauses, player withdrawal, and explicit regeneration of future unplayed rounds.
- Historical match and round assignments that remain unchanged once locked.

## Technology

- PHP 8.2+
- Laravel 12
- MySQL
- Blade
- Alpine.js
- Tailwind CSS 4
- Vite

The app does not require React, Vue, Inertia, Livewire, Redis, queues, WebSockets, Docker, or other runtime services beyond PHP and MySQL.

## Local setup

### Requirements

Install PHP, Composer, Node.js/npm, and MySQL locally. Create an empty database named `mainpadel` and make sure the database user has permission to run migrations.

### Install

From the project directory:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

On Windows PowerShell, use this instead of `cp`:

```powershell
Copy-Item .env.example .env
```

Update the database values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mainpadel
DB_USERNAME=root
DB_PASSWORD=
```

Then run the database migrations and build the frontend assets:

```bash
php artisan migrate
npm run build
```

Start the local server:

```bash
php artisan serve
```

Open `http://localhost:8000`, register an account, and create a Game.

Never commit `.env` or real database credentials. Use environment variables managed by the deployment host for production.

## Drawing Engine

The most important domain component lives in [`app/Services/Drawing`](app/Services/Drawing). It is isolated from controllers, Blade views, and Eloquent persistence so it can be tested with plain input and output objects.

The engine:

1. Generates bounded guided candidates rather than using a simple random shuffle.
2. Validates hard constraints before considering fairness.
3. Scores match-count imbalance, rest imbalance, repeated partners, repeated opponents, and consecutive rests.
4. Uses configurable weights and deterministic tie-breaking.
5. Returns fairness metrics for automated comparison.

Required roster/court scenarios are covered by unit tests, including 4/1, 5/1, 6/1, 8/1, 8/2, 10/2, 12/2, and 12/3 players/courts.

## Useful commands

```bash
# Clear cached configuration before tests when local environment values changed
php artisan config:clear

# Run the full automated suite
php artisan test

# Check code style without changing files
vendor/bin/pint --test

# Build production assets
npm run build

# Cache Blade views for a production-like check
php artisan view:cache
```

The Composer test script also clears configuration before running the suite:

```bash
composer test
```

## Shared hosting deployment

The application is designed for ordinary PHP/MySQL hosting:

1. Point the web server document root at the project’s `public` directory.
2. Upload the application code without `.env`, or create `.env` directly on the server.
3. Install PHP dependencies with `composer install --no-dev --optimize-autoloader`.
4. Build assets during deployment with `npm run build`, or upload the generated `public/build` directory.
5. Configure production MySQL credentials and set `APP_DEBUG=false`.
6. Run `php artisan migrate --force`.
7. Cache the production configuration and Blade views with `php artisan config:cache` and `php artisan view:cache`.
8. Ensure `storage` and `bootstrap/cache` are writable by the web process.
9. Use file sessions, file cache, and synchronous jobs unless the hosting environment explicitly supports another option.

No queue worker, WebSocket server, Redis instance, or container runtime is required.

## Domain structure

- `app/Models` — User, Game/Tournament, Player, Round, Match, and roster relationships.
- `app/Http/Controllers` — thin HTTP actions for authentication and Game screens.
- `app/Services` — Game creation, drawing orchestration, scoring, standings, and roster management.
- `app/Services/Drawing` — framework-independent candidate generation, validation, scoring, history, and metrics.
- `resources/views` — Blade layouts, landing page, authentication screens, Game screens, and navigation.
- `tests/Unit/DrawingEngineTest.php` — Drawing Engine constraints, reproducibility, fairness, and edge cases.
- `tests/Feature` — authentication, ownership, and end-to-end Game flow coverage.

## Current scope and limitations

- Authentication currently covers registration, sign in, and sign out. Password reset, email verification, social login, roles, and admin features are not included.
- Auto rounds use the configured bounded heuristic documented in `config/mainpadel.php`.
- Completed match assignments and rounds are immutable. Score corrections, where allowed, update score fields and derived standings only.
- Fairness metrics are returned by the Drawing Engine and persisted on each round in the `rounds.drawing_metrics` JSON column for regression inspection.
- Games created before user ownership was introduced may have a null `user_id` and require an explicit data migration policy before they can be reopened.

## Source of truth

Product requirements are documented in [`prd.md`](prd.md), and UX/design constraints are documented in [`design.md`](design.md). Implementation sequencing and progress are tracked in [`task.md`](task.md).
