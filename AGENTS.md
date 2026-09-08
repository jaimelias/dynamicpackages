# AGENTS.md

## Description
`dynamicpackages` is a WordPress plugin for service reservations, including hotels, tours, transport, hourly rentals, and daily rentals.
The project uses PHP 8.1+, modern JavaScript, WordPress APIs, and jQuery Slim-compatible frontend code.
Prefer code that is easy to understand at a glance. Favor the smallest correct implementation that satisfies the requested behavior.
Simplicity must never come at the cost of security, correctness, or backward compatibility.
---
## Ecosystem
`dynamicpackages` is part of a 4-component suite:
- `dynamicpackages`: the current WordPress plugin and primary working repository.
- `minimalizr`: a required WordPress theme dependency, installed in `../../themes/minimalizr`.
- `dynamicaviation`: an optional WordPress plugin, installed in `../dynamicaviation`.
- `dy-core`: a shared library located inside this repository at `./dy-core`.
### Dependency Boundaries
There are no direct application-level dependencies between `dynamicpackages`, `dynamicaviation`, and `minimalizr`.
They must never call each other's functions, classes, or methods directly.
Shared behavior belongs in `dy-core`.
If functionality is required by more than one application, implement the reusable logic in `dy-core` and have each application consume the `dy-core` implementation independently.
Do not create direct dependencies such as:
`dynamicpackages -> dynamicaviation`
`dynamicpackages -> minimalizr`
`dynamicaviation -> dynamicpackages`
`minimalizr -> dynamicpackages`
The intended architecture is:
`dynamicpackages -> dy-core`
`dynamicaviation -> dy-core`
`minimalizr -> dy-core`
### dy-core Ownership
The canonical editable copy of `dy-core` is:
`dynamicpackages/dy-core`
Only modify `dy-core` from this repository.
Copies of `dy-core` present in `dynamicaviation` or `minimalizr` are published copies and must not be edited as part of work in `dynamicpackages`.
When a task requires shared behavior, modify the canonical `dynamicpackages/dy-core` implementation.
Do not automatically synchronize, overwrite, or modify copies of `dy-core` in sibling projects unless explicitly requested.
Assume publication of `dy-core` to other applications is a separate manual release step.
---
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
---
## Think Before Coding
Do not start by changing code when the requested behavior or existing implementation has not been understood.
Before implementing:
- Inspect the relevant existing code first.
- Identify existing helpers, abstractions, hooks, and conventions that already solve part of the problem.
- Do not assume undocumented behavior when it can be verified cheaply.
- Surface important assumptions.
- Surface meaningful tradeoffs when more than one valid implementation exists.
- Prefer the simpler implementation when it satisfies the same requirements.
- If an ambiguity materially affects correctness, identify it rather than silently choosing an interpretation.
- Do not invent requirements that were not requested.
When the intended behavior can be reasonably inferred from the existing code and request, proceed with the smallest safe interpretation instead of blocking progress unnecessarily.
---
## Minimum Necessary Implementation
Implement the minimum code required to solve the requested problem correctly.
Do not add speculative functionality.
Do not add:
- features that were not requested
- abstractions for a single-use case without a concrete reuse need
- configuration options that were not requested
- unnecessary extensibility
- unnecessary wrappers
- defensive code for scenarios that cannot reasonably occur
- new dependencies without clear justification
Prefer a direct 50-line implementation over a generalized 200-line implementation when both satisfy the same requirements safely and maintainably.
Do not optimize for hypothetical future requirements.
---
## Surgical Changes
Touch only what is necessary for the requested change.
When editing existing code:
- Do not improve unrelated code.
- Do not reformat unrelated sections.
- Do not rewrite unrelated comments.
- Do not rename unrelated variables or methods.
- Do not refactor code merely because another style would be preferable.
- Match surrounding project conventions when they do not conflict with security or explicit project rules.
- If unrelated dead code is discovered, mention it rather than deleting it.
When the current change makes something unused, remove only the imports, variables, methods, or other code made obsolete by the current change.
Do not remove pre-existing dead code unless explicitly requested.
Keep functional changes separate from unrelated cleanup.
---
## General Code Style
Write code that is easy to read and understand at a glance.
Prefer:
- explicit intent
- simple control flow
- meaningful names
- small focused methods
- early returns
- guard clauses
- existing project abstractions
- predictable behavior
Avoid unnecessary cleverness.
### Exception Handling
Minimize `try { } catch (...) { }` usage.
Do not wrap code in `try/catch` merely as defensive programming.
Prefer, when appropriate:
- input validation
- guard clauses
- explicit precondition checks
- safe defaults
- WordPress error APIs
- checking return values
Use exceptions when the called API genuinely communicates failures through exceptions or when exception semantics are appropriate for the domain.
Do not silently swallow exceptions.
If an exception is caught, there must be a concrete reason for handling it at that layer.
---
## PHP Requirements
All new or modified PHP code must be compatible with PHP 8.1 or newer.
Use modern PHP 8.1 syntax where appropriate.
Prefer:
- `[]` array syntax
- spread operators where they improve clarity
- parameter type declarations
- return type declarations
- property type declarations
- nullable and union types when semantically appropriate
- constructor property promotion when it improves the implementation
- strict comparisons when the expected type is known
- explicit visibility on methods and properties
### Typing
New PHP functions and methods should be typed.
New or modified method parameters should use appropriate type declarations when compatible with the surrounding API.
New or modified methods should declare return types when compatible with the surrounding API.
New properties should be typed when their type is known.
Do not remove useful type information.
Do not weaken a type merely to silence static analysis.
However, do not introduce native type declarations that break an existing WordPress hook, callback, inheritance contract, public API, or supported backward-compatible behavior.
When WordPress passes values with broader or dynamic types, model those values accurately rather than forcing an incorrect narrow type.
### Visibility and Static Methods
Every class method must use explicit visibility:
- `public`
- `protected`
- `private`
Every class property must use explicit visibility.
Use `private` by default for implementation details that do not need subclass access.
Use `protected` only when subclass access is intentional.
Use `public` only for the intended external API.
Declare methods `static` only when their behavior does not depend on instance state and making them static is appropriate to the existing architecture.
Do not make methods static merely as a stylistic preference.
### Control Flow
Prefer early returns and guard clauses over deeply nested conditionals when they improve readability.
Avoid unnecessary type juggling.
Keep functions and methods focused.
Do not introduce abstractions without a concrete requirement.
Search for existing helpers before creating new ones.
If logic is shared by multiple applications, prefer implementing it in `dy-core`.
---
## JavaScript Requirements
Write modern ES6+ JavaScript.
Prefer:
- `const` by default
- `let` when reassignment is required
- arrow functions where appropriate
- template literals when they improve readability
- destructuring when it improves clarity
- array methods such as `map`, `filter`, `find`, and `some` when appropriate
Do not use `var`.
Prefer arrow functions over traditional function expressions unless JavaScript semantics require a dynamic `this`, `arguments`, constructor behavior, or another feature that arrow functions do not provide.
### jQuery Slim Compatibility
JavaScript must remain compatible with jQuery Slim when jQuery is used.
Do not assume APIs excluded from jQuery Slim are available.
In particular, do not introduce dependencies on jQuery Ajax or jQuery effects APIs when the runtime only guarantees jQuery Slim.
Prefer native browser APIs where appropriate.
For HTTP requests, prefer the project's existing request abstraction or modern browser APIs such as `fetch()` when compatible with the existing implementation.
### Frontend Performance
Avoid render-blocking patterns.
Do not introduce unnecessary synchronous frontend work during initial page rendering.
Prefer:
- deferred execution when appropriate
- event-driven initialization
- loading code only where needed
- DOM queries scoped to the relevant component
- avoiding repeated DOM lookups inside loops
- avoiding unnecessary layout recalculations
- WordPress enqueue APIs for assets
Do not move code asynchronously when execution order is required for correctness.
---
## WordPress Development Rules
Use WordPress APIs instead of duplicating functionality already provided by WordPress.
Preserve existing:
- hooks
- filters
- public method signatures
- public function signatures
- option names
- metadata keys
- database schemas
- REST routes
- AJAX actions
- cron hooks
- shortcodes
- external contracts
unless the task explicitly requires changing them.
When handling external input, use appropriate validation and sanitization.
Escape output at the point of output using the appropriate WordPress escaping function.
For privileged operations, verify user capabilities and nonces where applicable.
Use `$wpdb->prepare()` or appropriate WordPress database APIs when SQL contains dynamic values.
Do not disable, bypass, or suppress security checks merely to make automated validation pass.
---
## WordPress Core Reference
Do not scan `wp-admin` or `wp-includes` extensively.
For questions about WordPress functions, classes, methods, hooks, filters, parameters, or return values, consult the official WordPress Developer Reference first:
`https://developer.wordpress.org/reference/`
Use the local installed WordPress core only when necessary to verify implementation details or behavior specific to the installed WordPress version.
When inspecting local WordPress core:
- locate the relevant symbol first
- read only the relevant function, class, or nearby implementation
- avoid loading entire core files when a smaller section is sufficient
- avoid broad recursive scans of `wp-admin` or `wp-includes`
Do not load large sections of WordPress documentation or WordPress core into context when a targeted reference is sufficient.
---
## Polylang
Polylang is an optional dependency.
`dynamicpackages` must not assume Polylang is installed or active unless the relevant feature explicitly requires it.
Code integrating with Polylang must degrade safely when Polylang is unavailable.
Before using Polylang functions or APIs, verify availability where necessary.
For questions about Polylang functions, consult:
`https://polylang.pro/documentation/support/developers/function-reference/`
For Polylang filters, consult:
`https://polylang.pro/documentation/support/developers/filter-reference/`
Prefer official Polylang developer documentation before inspecting plugin source code.
If local Polylang source inspection is necessary:
- locate the relevant symbol first
- inspect only the relevant implementation
- avoid scanning the entire plugin
- do not modify Polylang source
---
## Available CLI Tools
The project provides the following primary development tools:
- WP-CLI: `wp`
- Composer: `composer`
- PHPStan: `vendor/bin/phpstan`
- PHP_CodeSniffer: `vendor/bin/phpcs`
- PHP Code Beautifier and Fixer: `vendor/bin/phpcbf`
- PHP Parallel Lint: `vendor/bin/parallel-lint`
Prefer Composer scripts when an equivalent project command exists.
For example, prefer:
`composer phpstan`
over:
`vendor/bin/phpstan analyse`
Prefer:
`composer check`
when performing the complete standard validation workflow.
---
## PHP Syntax Validation
PHP syntax must be validated after modifying PHP code.
Run:
`composer lint`
This uses PHP Parallel Lint across the project while excluding third-party dependency directories.
Syntax errors introduced by a change must always be fixed before the task is considered complete.
Because syntax validation is fast, it should normally be the first validation performed after PHP changes.
---
## PHPStan
PHPStan is the primary static analysis tool.
The project uses `szepeviktor/phpstan-wordpress` so PHPStan can understand WordPress functions, classes, constants, hooks, filters, and WordPress-specific dynamic behavior.
The PHPStan configuration is stored in:
`phpstan.neon.dist`
Run:
`composer phpstan`
after modifying PHP code.
`dy-core` is part of the PHPStan analysis scope because its canonical source lives inside this repository.
Do not analyze sibling projects as part of the normal `dynamicpackages` PHPStan run.
New PHPStan errors introduced by a change must be fixed before the task is considered complete.
Do not:
- add broad `ignoreErrors` rules
- introduce unnecessary baselines
- suppress errors simply to make PHPStan pass
- weaken PHPStan configuration to hide a new problem
A targeted suppression is acceptable only when PHPStan cannot accurately model valid behavior and there is a clear technical justification.
Prefer fixing the underlying type or control-flow problem.
---
## WordPress Coding Standards
PHP_CodeSniffer with WordPress Coding Standards is used for WordPress-specific code quality checks.
The PHPCS configuration is stored in:
`phpcs.xml.dist`
Run:
`composer phpcs`
after modifying PHP code.
The project currently uses:
- `WordPress-Core`
- `WordPress-Extra`
PHPCS findings should be evaluated as potential defects, compatibility issues, security issues, or coding-standard violations.
Do not mechanically suppress PHPCS rules merely to make validation pass.
### Automatic Fixes
PHP Code Beautifier and Fixer is available through:
`composer phpcbf`
Do not run project-wide automatic fixes without first understanding the resulting scope.
Avoid creating large unrelated formatting diffs while implementing focused changes.
For a focused task, prefer applying automatic fixes only to affected files when practical.
Functional changes and unrelated formatting changes should not be mixed unnecessarily.
---
## Composer
Composer manages development tooling and PHP dependencies.
Do not manually modify files inside:
`vendor/`
When `composer.json` is modified, run:
`composer validate`
When Composer dependencies are added, removed, or updated, also run:
`composer audit`
Commit `composer.lock` when dependency changes intentionally modify it.
Do not update unrelated dependencies as part of a focused task.
---
## Validation Workflow
For PHP changes, use the following validation order:
1. PHP syntax validation
2. PHPStan
3. WordPress Coding Standards
The standard commands are:
`composer lint`
`composer phpstan`
`composer phpcs`
For substantial PHP changes or before completing a task, prefer running:
`composer check`
`composer check` executes the project's standard PHP validation suite.
When iterating on a specific problem, the smallest relevant validation command may be executed first.
Before completing the task, all validation relevant to the change should pass.
---
## Validation Failures
Do not report a task as successfully completed when a relevant validation command is failing because of the current change.
If validation exposes an existing unrelated problem, distinguish clearly between:
- failures introduced by the current change
- failures that already existed
Do not fix unrelated existing failures unless they prevent completion of the requested task or the user explicitly asks for them to be fixed.
Do not modify unrelated code merely to make a global validation count smaller.
---
## Change Discipline
Keep changes scoped to the requested task.
Do not perform unrelated refactors while implementing a focused feature or bug fix.
Before introducing a new:
- class
- helper
- trait
- abstraction
- utility
- Composer dependency
search the existing codebase for equivalent functionality.
Prefer extending existing architecture over building parallel implementations.
Preserve backward compatibility unless the requested change explicitly requires breaking it.
When changing `dy-core`, consider that the code may eventually be consumed by:
- `dynamicpackages`
- `dynamicaviation`
- `minimalizr`
Do not modify those sibling applications automatically.
---
## Completion Criteria
For PHP work, a task is normally complete when:
- the requested behavior has been implemented
- only necessary files and code were changed
- existing public behavior has been preserved unless intentionally changed
- PHP syntax validation passes
- PHPStan passes for the affected code
- PHPCS passes or remaining findings are known pre-existing issues
- no new unnecessary abstractions or dependencies were introduced
- no unrelated cleanup was included
The standard final validation command is:
`composer check`
When reporting completion, state which validation commands were actually executed and their result.
If validation could not be executed, state that explicitly instead of implying that it passed.
If `dy-core` was modified, mention that its published copies in sibling applications may need to be synchronized manually as a separate release step.
