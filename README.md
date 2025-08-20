# SATORI Suite

> 📌 **Start here:** See [docs/Next_Steps.md](docs/Next_Steps.md) for the current roadmap & milestones.

# SATORI Membership Suite — Project Skeleton

This is a starter folder hierarchy for local development.

- Created: 2025-08-19
- Intended use: clone/move into your local dev workspace, then init git.

## Structure
- core/ — SATORI Core plugin
- modules/ — Each SATORI module as a standalone plugin (can run alone or together)
- suite-installer/ — Optional bundle installer shell (deferred)
- docs/ — Specifications, PDFs, diagrams
- assets/ — Shared branding assets (logos, UI tokens, etc.)
- build/ — Release artifacts (zips)
- scripts/ — Release, packaging, helper scripts
- config/ — Local config (e.g., endpoints for updates)
- tests/ — PHPUnit/E2E scaffolding


## CI / QA
- GitHub Actions: `.github/workflows/php-qa.yml` runs PHPCS + PHPStan on pushes/PRs.
- Packaging: `.github/workflows/package-release.yml` zips Core and each module on tag `v*.*.*`.
- Composer: run `composer install`, then `composer phpcs` / `composer phpstan`.
