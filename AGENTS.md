# AGENTS.md

`dynamicpackages`: WordPress plugin for hotel, tour, transport, hourly and daily rental reservations.

- Before coding, read `dy-core/docs/CODING.md`; its rules are mandatory project-wide. Resolve documentation links relative to their containing file; project paths start at this repository root.
- Root-level `dy-core/` is the canonical library shared by plugins/themes. Do not modify or delete its contents, including unused functions, unless explicitly requested.
- Prefer targeted `rg` searches over recursive file reads

## Common Entry Points

- Main plugin bootstrap: `dynamicpackages.php`
- Admin screens: `admin/`
- Frontend booking flow: `public/`
- Shared helpers: `includes/`
- Canonical shared library: `dy-core/`