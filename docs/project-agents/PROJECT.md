# PROJECT.md

## Project Role
`dynamicpackages` is the primary WordPress plugin for service reservations, including hotels, tours, transport, hourly rentals, and daily rentals.
The project uses PHP 8.1+, modern JavaScript, WordPress APIs, and jQuery Slim-compatible frontend code.
Prefer code that is easy to understand at a glance. Favor the smallest correct implementation that satisfies the requested behavior. Simplicity must never come at the cost of security, correctness, or backward compatibility.
## Ecosystem
`dynamicpackages` is part of a four-component suite:
- `dynamicpackages`: the current WordPress plugin and primary working repository.
- `minimalizr`: a required WordPress theme dependency, installed in `../../themes/minimalizr`.
- `dynamicaviation`: an optional WordPress plugin, installed in `../dynamicaviation`.
- `dy-core`: the shared library whose canonical editable source is `./dy-core`.
## Dependency Boundaries
There are no direct application-level dependencies between `dynamicpackages`, `dynamicaviation`, and `minimalizr`.
They must never call each other's functions, classes, or methods directly.
Shared behavior belongs in `dy-core`.
If functionality is required by more than one application, implement the reusable logic in `dy-core` and have each application consume the `dy-core` implementation independently.
Do not create direct dependencies such as:
- `dynamicpackages -> dynamicaviation`
- `dynamicpackages -> minimalizr`
- `dynamicaviation -> dynamicpackages`
- `minimalizr -> dynamicpackages`
The intended architecture is:
- `dynamicpackages -> dy-core`
- `dynamicaviation -> dy-core`
- `minimalizr -> dy-core`
## dy-core Ownership
The canonical editable copy of `dy-core` is:
`dynamicpackages/dy-core`
Only modify `dy-core` from this repository.
Copies of `dy-core` present in `dynamicaviation` or `minimalizr` are published consumer copies and must not be edited as part of work in `dynamicpackages`.
When a task requires shared behavior:
1. Modify the canonical `dynamicpackages/dy-core` implementation.
2. Keep shared logic application-agnostic.
3. Do not automatically synchronize or overwrite published copies in sibling projects unless explicitly requested.
4. Assume publication of `dy-core` to sibling applications is a separate manual release step.
When changing public behavior in `dy-core`, consider all ecosystem consumers.
## Repository Scope
By default, edits must remain inside the `dynamicpackages` repository.
Reading `../dynamicaviation` and `../../themes/minimalizr` is allowed when necessary to understand integrations, compatibility, or existing behavior.
Do not modify sibling projects unless explicitly requested.
Never modify:
- `vendor/`
- `node_modules/`
- WordPress core files
- third-party generated dependencies
Do not extensively scan unrelated directories when a targeted search can answer the question.
## Application Domain
Preserve existing reservation-domain contracts unless the requested task explicitly changes them.
Before changing behavior involving reservation types, availability, dates, capacity, prices, totals, booking state, metadata, or external integrations, inspect the existing implementation and its callers first.
Do not invent business rules or silently reinterpret units, currencies, dates, identifiers, or persisted values.
If behavior is generic to more than one ecosystem application, prefer `dy-core` instead of introducing an application-specific duplicate.
## Static Analysis Scope
`dy-core` is part of the normal PHPStan analysis scope because its canonical source lives inside this repository.
Do not analyze sibling applications as part of the normal `dynamicpackages` PHPStan run.
## Completion Notes
If `dy-core` was modified, explicitly mention that its published copies in sibling applications may need to be synchronized manually as a separate release step.
