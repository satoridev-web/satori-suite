# SATORI Suite — Tests Folder

This folder contains mock JSON payloads and helper files for testing the
**SATORI Core Update Client**.

---

## Purpose
The Core update client supports local/mock payloads in `development` and
`staging` environments. This allows us to simulate plugin updates without
hitting the live update server.

- `update-stable.json` → mimics a stable channel release (e.g. `0.1.1`)
- `update-beta.json` → mimics a beta channel release (e.g. `0.2.0-beta1`)

These files are referenced automatically if:

- `WP_ENVIRONMENT_TYPE` is `development` or `staging`, **and**
- no explicit `SATORI_CORE_UPDATE_MOCK` constant is defined.

---

## How It Works
The bootstrap in `satori-core.php` checks:

1. Environment variable `SATORI_CORE_UPDATE_MOCK` (highest priority).
2. `WP_ENVIRONMENT_TYPE` (`development`, `staging`, `production`).
3. Falls back to production (no mock, live server only).

### Example defaults
- **Development** → `tests/update-stable.json`
- **Staging** → `tests/update-beta.json`
- **Production** → disables mock (live server only)

---

## Acceptance Test
1. Set `WP_ENVIRONMENT_TYPE` to `development`.
2. Ensure `tests/update-stable.json` contains a higher version than installed.
3. In WP Admin → Plugins, you should see  
   *“Update available: 0.1.1”* for SATORI Core.
4. Repeat with `staging` to confirm `update-beta.json` is loaded.
5. Switch to `production` → update should disappear (live server only).

---

## Notes
- You can override the file path using  
  `define('SATORI_CORE_UPDATE_MOCK', '/absolute/path/to/file.json');`
- Always clear transients (`wp transient delete --all` or Tools → Debug → Clear caches) after changing env.
