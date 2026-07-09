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

## Setup

```bash
composer setup-bun        # install, .env, key, migrate, build (or `composer setup` for npm)
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
