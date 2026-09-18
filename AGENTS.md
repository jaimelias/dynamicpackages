# AGENTS.md

`dynamicpackages`: WordPress plugin for hotel, tour, transport, hourly and daily rental reservations.

- Use `.codex/map.txt` to locate relevant code before searching the repository.
- Prefer targeted `rg` searches over recursive file reads, read `dy-core/docs/RG.md`.
- Before coding, read `dy-core/docs/CODING.md`; its rules are mandatory project-wide. Resolve documentation links relative to their containing file; project paths start at this repository root.
- Root-level `dy-core/` is the canonical library shared by plugins/themes. Do not modify or delete its contents, including unused functions, unless explicitly requested.


## Common Entry Points
- Main plugin bootstrap: `dynamicpackages.php`
- Admin screens: `admin/`
- Frontend booking flow: `public/`
- Shared helpers: `includes/`
- Canonical shared library: `dy-core/`

## DEV Tools
- Local site: `http://localhost:8888/wordpress`
- Read the project's `composer.json` for tools and configuration.
- Runtime: PHP 8.1 (Apache)
- Diagnostics: `Query Monitor` is installed; use it when investigating runtime notices, queries, and asset dependencies.

