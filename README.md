# SATORI Suite

The SATORI Suite is a modular WordPress plugin ecosystem designed for membership, events, and content management.  
It provides a **core framework** plus optional modules that can be used standalone or together.

---

## Modules (planned)

- **SATORI Core** – shared utilities, updater, bootstrap guard
- **SATORI Forms** – frontend forms, submissions, integration
- **SATORI Events** – event CPT, archives, filters
- **SATORI Members** – membership system
- **SATORI Profiles** – profile management
- **SATORI Payments** – secure checkout & workflows
- **SATORI Search** – enhanced search experience
- **SATORI Filters** – faceted filters
- **SATORI Notifications** – email & in-app notices
- **SATORI Reports** – reporting dashboards

Each module can be used independently or integrated seamlessly with others.

---

## Development

This repo is organised as a **monorepo**:

```
satori-suite/
 ├─ core/       → base framework
 ├─ modules/    → individual plugins
 ├─ docs/       → specifications and project documents
 ├─ assets/     → diagrams, media
 └─ scripts/    → helper scripts (e.g., link-modules.sh)
```

---

## Documentation

- [Project Scope (v0.2)](docs/Satori_Membership_Suite_Project_Scope_v0-2.pdf)
- [Core Spec (v0.2)](docs/Satori_Core_Install_Bootstrap_Hooks_v1_v0-2.pdf)
- [Branching & Versioning Guide](docs/Branching_and_Versioning.md)
- [Changelog](CHANGELOG.md)
- [Working with Ms Chat](docs/Working_with_Ms_Chat.md)
- [Next Steps](docs/NEXT_STEPS.md)
- [Distribution Architecture Diagram](docs/Satori_Distribution_Architecture_v0-2.png)

---

## Contributing

1. Clone the repo:
   ```bash
   git clone https://github.com/satoridev-web/satori-suite.git
   ```
2. Install symlinks into your LocalWP site:
   ```bash
   bash scripts/link-modules.sh
   ```
3. Create a feature branch:
   ```bash
   git checkout -b feat/core/updater-client
   ```
4. Commit using [Conventional Commits](https://www.conventionalcommits.org/).
5. Open a PR → squash & merge into `main`.

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

---

© 2025 Satori Graphics Pty Ltd
