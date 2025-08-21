# SATORI Core — Next Steps (MVP Focus)

> **Scope:** Core plugin only.  
> **Authoritative:** Mirrors the Core portion of `/docs/NEXT_STEPS.md`.  
> **When in doubt:** The project-wide roadmap in `/docs/NEXT_STEPS.md` is the source of truth.

---

## Immediate Priorities
- [ ] **CoreGuard** — detect Core presence/version; single admin notice; one-click Install/Activate/Update (nonces + `WP_Upgrader`)
- [ ] **Update Client** — env-aware (dev/staging mock JSON, prod live); caches; manual recheck; logs source (`mock-json|remote|cache`)
- [ ] **Tools/Settings** — WP table layout; tabs: General, Debug & Telemetry, Advanced
- [ ] **Logger & Debug Footer** — file logs with redaction; admin footer link to today’s log; capability-gated
- [ ] **Diagnostics** — copy-to-clipboard (WP/PHP versions, Core version, active SATORI modules, last update check, masked license)
- [ ] **Public Hooks** — `satori/core/loaded`, `satori/core/register_settings_tabs`, `satori/core/register_capabilities`, `satori/core/register_update_channels`
- [ ] **Security/i18n/a11y** — nonces, caps, escape/sanitize, `__()` domain `satori-core`, keyboard/labels/contrast

---

## Testing Checklist (Acceptance)
- **CoreGuard**
  - [ ] Remove/deactivate Core → activate a module → notice appears → click → Core installs/activates → module boots
- **Update Client**
  - [ ] Dev (stable mock) shows update to `0.1.1`
  - [ ] Staging (beta mock) shows update to `0.2.0-beta1`
  - [ ] Production (live) shows no update until endpoint is live
  - [ ] “Recheck updates” clears site/non-site transients and refreshes
- **Tools/Settings**
  - [ ] Debug toggle immediately affects logging + footer
  - [ ] “Recheck updates” and “Clear SATORI caches/transients” complete without errors
  - [ ] “Export settings” downloads JSON
- **Logging & Diagnostics**
  - [ ] INFO/WARNING/ERROR entries produced (simulate one failed remote request)
  - [ ] License/token redaction confirmed
  - [ ] Debug footer visible only for `manage_options`
- **Multisite**
  - [ ] Network-admin CoreGuard respects network caps; no duplicate notices on sub-sites

---

## DevX & CI
- [ ] PHPCS (WordPress CS) passes
- [ ] PHPStan level 5–6 for Core classes
- [ ] Composer autoload `Satori\` → `/core`
- [ ] GitHub Actions `php-qa.yml` green

---

## File Structure (Core)

/core  
├─ satori-core.php  
├─ /includes  
│ ├─ CoreGuard.php  
│ ├─ AdminSettings.php  
│ ├─ Logger.php  
│ ├─ UpdateClient.php  
│ ├─ Diagnostics.php  
│ └─ Helpers.php  
└─ /assets  
  └─ admin.css / admin.js  

---

## References
- **Definition of Done:** `docs/Core_MVP_Definition_of_Done.md`
- **Mocks:** `tests/update-stable.json`, `tests/update-beta.json`
- **Env Toggle Snippet:** `wp-config.php` block for `SATORI_CORE_UPDATE_MOCK` and `WP_ENVIRONMENT_TYPE`
- **Working Practices:** `docs/Working_with_Ms_Chat.md`

---

**Version:** v0.2  
**Last Updated:** 21-Aug-2025
