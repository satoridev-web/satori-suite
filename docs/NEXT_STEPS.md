# SATORI Suite — Next Steps Checklist (v0.2 Baseline)

This file tracks our agreed milestones after establishing the v0.2 foundation.

---

## Phase 1 — Core Foundation (Aug–Sep 2025)
- [ ] Finalize **SATORI Core** bootstrap & installer guard
- [ ] Implement Core update client (`updates.wordpressed.com.au`)
- [ ] Add initial public hooks (capabilities, settings, notifications)
- [ ] Write quick-start Core developer doc

## Phase 2 — First Modules (Sep–Oct 2025)
- [ ] **SATORI Forms** — baseline form submission
  - [ ] Frontend shortcode form
  - [ ] Admin form builder (basic fields)
  - [ ] Email notifications + spam protection
- [ ] **SATORI Events** — archive + single template baseline
  - [ ] CPT + taxonomy
  - [ ] Archive shortcode & filters
  - [ ] Responsive grid/list view

## Phase 3 — Membership & Payments (Oct–Nov 2025)
- [ ] **SATORI Members**
  - [ ] Basic registration & profiles
  - [ ] Roles / caps integration with Core
- [ ] **SATORI Payments**
  - [ ] Stripe/PayPal baseline
  - [ ] Secure checkout flow

## Phase 4 — Polish & Integrations (Nov–Dec 2025)
- [ ] **SATORI Notifications** — admin + user alerts
- [ ] **SATORI Search/Filters** — integrated UI
- [ ] **SATORI Reports** — use Tables infra
- [ ] Docs, support, and first public release on **wordpressed.com.au**

---

## Optional Future Modules (Deferred)
- SATORI Tables (DataTables integration)
- SATORI Folders (Media/File manager)
- Enterprise-only: SSO, org hierarchies, audit sinks

---

## Notes
- Use this checklist as a living document in `/docs`.
- Mark milestones as complete and update scope as we progress.

---

## Cross-reference
- See **Core_MVP_Definition_of_Done.md** for full Phase 1 acceptance criteria.
- Reminder: re-package Core with the AdminSettings version constant fix baked in before tagging `v0.1.0`.
