# SATORI Core — Docs Index

This folder is the **developer hub** for the Core plugin only.

## Start Here
- 🚀 [NEXT_STEPS_CORE.md](./NEXT_STEPS_CORE.md) — Immediate Core MVP tasks & acceptance checks
- ✅ [../../docs/Core_MVP_Definition_of_Done.md](../../docs/Core_MVP_Definition_of_Done.md) — Core MVP DoD (authoritative)

## Implementation
- 🧩 `../includes/` — Core classes (CoreGuard, UpdateClient, Logger, Diagnostics, Helpers)
- 🎛 `../assets/` — Admin CSS/JS  
- ⚙️ `../../tests/` — Mock update payloads (`update-stable.json`, `update-beta.json`) + README

## Environment & Updates
- 🔁 Use `SATORI_CORE_UPDATE_MOCK` and `WP_ENVIRONMENT_TYPE` in `wp-config.php`
- 🔧 Clear caches via **SATORI → Tools/Settings → Clear SATORI Caches/Transients**
- 🔎 “View today’s log” from the debug footer when **Debug Mode** is enabled

## Cross‑References
- 🗺 Project‑wide roadmap: [`../../docs/NEXT_STEPS.md`](../../docs/NEXT_STEPS.md)
- 🤝 Working conventions: [`../../docs/Working_with_Ms_Chat.md`](../../docs/Working_with_Ms_Chat.md)

---

**Notes**
- Keep Core docs focused on code, testing, and release mechanics.
- If a doc applies to the whole suite, add it under `/docs` and link to it here.
