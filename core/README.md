# SATORI Core (Phase 1 — Core MVP)

Baseline scaffold for Phase 1.

## Install
- Copy the `satori-core/` folder into `wp-content/plugins/`
- Activate **SATORI Core** in WP Admin → Plugins.
- Settings live in **SATORI → Tools/Settings**.

## Contents
- `core/admin/AdminSettings.php` — Settings UI
- `core/includes/Logger.php` — Daily file logger
- `core/includes/Diagnostics.php` — Diagnostics endpoint
- `core/includes/UpdateClient.php` — Update hooks (stub for MVP)
- `core/includes/Helpers.php` — Helpers
- `core/includes/CoreGuard.php` — Guard for other modules

## Next
- Wire `UpdateClient` to updates.wordpressed.com.au
- Expand public hooks and tabs
- Footer debug bar toggle
