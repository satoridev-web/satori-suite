# Core MVP – Definition of Done

## 1) CoreGuard (must-have)
- Detect Core presence + version.
- If missing/old → show single admin notice with a one‑click Install/Activate/Update action.
- Safe and WP‑native:
  - current_user_can( 'install_plugins' ) / network caps on multisite
  - Nonces, check_admin_referer()
  - Uses WP_Upgrader APIs
- No duplicate notices across modules (dedupe via transient).

**Acceptance test**
- Deactivate/remove Core → activate any module → see notice → click → Core installs/activates → module boots.

---

## 2) Update Service Client (baseline)
- Reads Update URI: https://updates.wordpressed.com.au/plugins/<slug>.
- Implements filter path to pull JSON (new_version, package, changelog) and populate pre_set_site_transient_update_plugins.
- (PRO‑ready) Can attach license key from settings to request, but no gating yet.
- “View details” modal supported via plugins_api filter (uses sections).

**Acceptance test**
- Mock endpoint (or local JSON) returns higher version → native WP “Update available” appears → update works.

---

## 3) Tools/Settings screen (Core → “SATORI”)

**Location**
- Settings → SATORI (single page Core owns; modules add tabs via hook).

**Tabs (Core MVP)**
- General
  - Site ID (read‑only)
  - Updates channel (stable | beta) – optional
  - License key field (string, stored but not enforced yet)
- Debug & Telemetry
  - Debug Mode (toggle) — when ON:
    - Enables verbose logging (see §4)
    - Shows a compact “debug bar” in admin footer (Core‑only)
    - Adds a “Copy diagnostics” button
  - Telemetry (toggle) — OFF by default (no data sent in MVP)
    - Info text with link to policy (placeholder)
- Advanced
  - “Recheck updates” button (runs wp_update_plugins())
  - “Clear SATORI caches/transients”
  - “Export settings” (JSON download)

**Storage**
- satori_core_options (single option array).
- Sanitize/escape every field.
- Capability: manage_options.

**Acceptance test**
- Toggling Debug updates DB and affects logging/debug footer immediately.
- “Recheck updates” triggers a transient refresh without errors.

---

## 4) Logging & Debug (when toggle ON)
- Logger class:
  - Writes to wp-content/uploads/satori/logs/satori-YYYY-MM-DD.log (auto‑create folder)
  - Levels: info, warning, error, debug (gated by Debug Mode)
  - Redacts secrets (license, tokens) automatically
- Admin Debug Footer (minimal, Core‑only):
  - “SATORI Core vX.Y.Z | Debug: ON | Log: view latest (opens in new tab)”
  - Safe: only visible to manage_options
- Diagnostics (copy to clipboard):
  - WP version, PHP version, active SATORI modules, Core version, last update check results, license masked.

**Acceptance test**
- Enable Debug → perform an action (e.g., update check) → log line appears; footer shows Debug ON; diagnostics copies to clipboard.

---

## 5) Public hooks (initial set)
- satori/core/loaded (fires after Core boots)
- satori/core/register_settings_tabs (modules add tabs)
- satori/core/register_capabilities (modules declare caps)
- satori/core/register_update_channels (optional for beta/nightly in future)

**Acceptance test**
- Dummy module registers a Settings tab and it renders correctly within Core page.

---

## 6) Security, i18n, a11y
- Nonces on all POST/AJAX; caps checks everywhere.
- Escape on output; sanitize on save.
- Strings wrapped in __() with satori text domain.
- Settings page: headings/labels associated, keyboard/tab order, color contrast OK.

**Acceptance test**
- Submit settings with bad nonce → rejected.
- PoEdit scan finds translatable strings.
- WAVE (or manual) finds no landmark/labeling issues.

---

## 7) Multisite behavior
- CoreGuard works at network‑admin level for network‑activated modules.
- Settings page appears per site for now (network settings deferred).
- Update client works site‑level (network aggregation deferred).

**Acceptance test**
- In multisite, installing Core via notice respects network caps; no duplicate notices on sub‑sites.

---

## 8) DevX & CI
- PHPCS (WordPress CS) passes.
- PHPStan at a reasonable level (e.g., 5–6) for Core classes.
- Composer autoload in place (Satori\ → core/).
- GitHub Actions (php-qa.yml) green on PR.

**Acceptance test**
- composer phpcs && composer phpstan pass locally and in CI.

---

## 9) Docs
- /docs: short Core Settings page screenshot + field descriptions.
- Update the Scope and Core Spec with:
  - Settings/Debug behavior
  - Update client behavior
  - Logging locations & retention

---

## Minimal file structure (Core)
/core
├─ satori-core.php (plugin header, boot)
├─ /includes
│  ├─ CoreGuard.php
│  ├─ AdminSettings.php (menu, tabs, forms, save)
│  ├─ Logger.php (file logger with redaction)
│  ├─ UpdateClient.php (filters + request/parse JSON)
│  ├─ Diagnostics.php (collect & copy)
│  └─ Helpers.php (sanitizers, caps, paths)
└─ /assets
    └─ admin.css / admin.js (debug footer, copy button)

---

## Implementation notes (quick wins)

### Settings tabs API
Core renders tabs; modules hook:

```php
add_action('satori/core/register_settings_tabs', function($tabs){
    $tabs['forms'] = [
        'title' => __('Forms','satori'),
        'callback' => 'Satori\\Forms\\Admin\\settings_tab'
    ];
    return $tabs;
});
```
