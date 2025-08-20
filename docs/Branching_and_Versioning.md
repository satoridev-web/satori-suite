# SATORI Git Branching & Versioning Guide (v1)

## 0) TL;DR (defaults)
- Default branch: **`main`**
- Branches: **`type/module-short/short-desc`**
  - Examples: `feat/forms/conditional-logic`, `fix/core/updater-nonce`, `docs/scope/v0-3-notes`
- Merge strategy: **Squash & Merge** into `main`
- Tags: **SemVer**, annotated: `vMAJOR.MINOR.PATCH` (e.g., `v0.3.0`)
- Changelog: update in PR, tag after merge

---

## 1) Branch naming

**Pattern**
```
type/module-short/short-desc
```
**Types**
- `feat` – new feature
- `fix` – bug fix
- `refactor` – internal change, no new features
- `perf` – performance improvements
- `docs` – documentation only
- `chore` – non‑code changes (config, tooling)
- `ci` – continuous integration / workflows
- `release` – optional staging branch for a release (rare)

**Module short names**
- `core`, `forms`, `events`, `members`, `payments`, `search`, `filters`, `notifications`, `reports`, `suite-installer`

**Examples**
```
feat/forms/conditional-logic
fix/core/guard-multisite
docs/reports/metrics-outline
ci/package/artifacts-zip
```

---

## 2) Working model (trunk‑based, short‑lived)
1. **Branch off `main`:**
   ```bash
   git checkout -b feat/forms/conditional-logic
   ```
2. Commit small, focused changes (see Conventional Commits below).
3. Open a PR → **Squash & Merge** into `main`.
4. Delete the branch after merge.

> Why squash? Keeps `main` linear and tidy; your PR title becomes the single commit on `main`.

---

## 3) Commit messages (Conventional Commits)
Format:
```
<type>(<scope>): short summary

[optional body]
[optional footer(s)]
```
- **type**: `feat`, `fix`, `docs`, `refactor`, `perf`, `test`, `chore`, `ci`
- **scope**: module or area: `core`, `forms`, `events`, etc.

**Examples**
```
feat(forms): add conditional logic to field visibility
fix(core): ensure updater uses nonce on ajax install
docs(core): add update service client diagram
```

---

## 4) Versioning policy (SemVer)
- **MAJOR**: breaking changes (rare pre‑v1)
- **MINOR**: new features (backward‑compatible)
- **PATCH**: bug fixes only

**Suite vs module versions**
- We tag the **suite** at the repo level (e.g., `v0.3.0`).
- Each plugin’s `Version:` header moves in lockstep **for now** (keeps releases simple).
- If/when modules diverge, we’ll introduce per‑module tags (documented in a future v2 guide).

**Pre‑releases**
```
v0.3.0-beta.1
v0.3.0-rc.1
```

---

## 5) Tagging & releases

**When to tag**
- After merging all PRs for a milestone and updating the changelog on `main`.

**How to tag**
```bash
git checkout main
git pull
git tag -a v0.3.0 -m "SATORI v0.3.0 – Forms+Events MVP"
git push origin v0.3.0
```

**CI packaging**
- Our GitHub Actions workflow packages **core** and each **module** into `build/`.
- (Optional next step) Publish artifacts to `wordpressed.com.au`/updates service.

---

## 6) Hotfixes
For urgent production fixes:
```
hotfix/core/update-uri-typo
```
Process:
1. Branch from `main`, implement the fix.
2. PR → Squash & Merge.
3. Tag a **PATCH**: `v0.2.1`.
4. Deploy packages.

---

## 7) Pull requests

**Title**
```
type(module): concise summary
```
**Description**
- What changed, why
- Screenshots/GIF if UX affected
- Testing notes (how to verify in LocalWP)

**Labels**
- `area:core`, `area:forms`, etc.
- `type:feat`, `type:fix`, `type:docs`, etc.
- `priority:P1|P2|P3` (optional)

**Checks to pass**
- CI ✅ (PHPCS, PHPStan)
- Reviewer approval (2+ when team grows; for now, 1 is fine)

---

## 8) Branch protection (when team grows)
- Protect `main`:
  - Require PR, code review, passing checks
  - Disallow direct pushes
  - Require linear history (optional, since we squash)
- Optionally protect long‑running `release/*` branches (if we use them)

---

## 9) Examples to copy‑paste

**Create a feature branch**
```bash
git checkout -b feat/events/tickets-cpt
```

**Push and set upstream**
```bash
git push -u origin feat/events/tickets-cpt
```

**Open PR** (on GitHub UI)  
Merge via **Squash & Merge**.

**Tag after merge**
```bash
git checkout main && git pull
git tag -a v0.3.0 -m "SATORI v0.3.0 – Events MVP"
git push origin v0.3.0
```

---

## 10) Changelog hygiene
- Keep `CHANGELOG.md` at repo root.
- Update in each PR if user‑visible changes.
- On tagging, roll PR summaries into the release notes.
