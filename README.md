# Beeorder — Group Ordering Service

Standalone backend service for the **Group Ordering** feature: multiple registered users
collaboratively place a single unified order from the same restaurant. One Group Leader
creates a session with a shareable link and a 5-minute join window; participants manage
independent sub-carts; the leader checks out as the sole payer, and per-participant
invoices split delivery fee, tax and discount equally.

This repo is developed as a standalone service that will later ship as a **microservice**
consumed by the live BeeOrder app. The REST APIs under `/api/v1` are the durable
deliverable; the Inertia/Vue frontend exists to showcase the feature and will be dropped
at adoption (the live client is a Flutter app).

## Stack

- Laravel 13 (PHP 8.3), MySQL (`group_ordering` database)
- Inertia.js + Vue 3 + Tailwind CSS v4, built with Vite via bun
- PHPUnit 12 (in-memory SQLite — keep migrations SQLite-compatible)

## Requirements

- PHP ≥ 8.3 with Composer (`pdo_mysql`, `mbstring`, `openssl` extensions)
- [bun](https://bun.sh) — or Node 20+ with npm (Vite 8 needs a modern runtime)
- MySQL running locally (XAMPP works); the default `.env` expects a
  `group_ordering` database on `127.0.0.1` as `root` with no password

## Quick start

```bash
# once: create the database
mysql -u root -e "CREATE DATABASE group_ordering"

composer setup-bun        # install, .env, key, migrate, build (or `composer setup` for npm)
php artisan db:seed       # demo catalogue: 10 restaurants, menus, users, addresses
php artisan serve         # http://127.0.0.1:8000
```

Sign in with any demo account — the login screen offers one-click sign-in
(password is `password` for all of them):

| Account | Email |
|---|---|
| Hasan Katteeb | `hasan@demo.beeorder.com` |
| Lina Haddad | `lina@demo.beeorder.com` |
| Omar Nassar | `omar@demo.beeorder.com` |
| Maya Aswad | `maya@demo.beeorder.com` |

**Try the group flow with two users:** pick a restaurant and start a group order in
one window, copy the invite link, open it in a private/incognito window signed in as
another account, join — the first window's lobby updates live within a few seconds.

Optional:

- `php artisan schedule:work` — runs the expiry sweep (FR-005) in the background so
  leader notifications fire without waiting for a request; expiry is also derived
  lazily on every read, so the demo works without it.
- **No MySQL?** Set `DB_CONNECTION=sqlite` in `.env` (drop the other `DB_*` lines),
  create an empty `database/database.sqlite`, then migrate and seed — everything is
  SQLite-compatible.

## Development

```bash
composer dev              # serve + queue + logs + vite, concurrently
composer test             # run the test suite
vendor/bin/pint           # code style
```

## Git workflow

Trunk-based development. `main` must always be green — CI runs Pint, PHPUnit and the
Vite build on every PR.

- No direct commits to `main`; all work goes through squash-merged PRs, reviewed before
  merge.
- One short-lived branch per user story or endpoint: `feat/us-001-initiate-group-order`,
  `fix/...`, `chore/...`, `docs/...`.
- Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`, `refactor:`,
  `style:`, `ci:`); reference spec IDs (US/FR/BR) where relevant.
- PR descriptions state which spec items are implemented and how they were tested. Open
  draft PRs early; keep PRs at a reviewable size.
