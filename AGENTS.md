## Description
`dynamicpackages` is a Wordpress plugin for service reservations (hotels, tours, transport, rentals per hour, rentals per day).
## Ecosystem
`dynamicpackages` is  part of a 4 components suite:
	- `minimalizr`: is a Wordpress theme and required dependency, installed in `../../themes/minimalizr`.
	- `dynamicaviation`: is an optional Wordpress plugin installed in .`./dynamicaviation` .
	- `dy-core`: is library installed in the plugin folder `dy-core`. Only from `dynamicpackages` folder the library dy-core can be edited. `dy-core` provides `minimalizr`, `dynamicpackages`, and `dynamicaviation` with a centralized helpers, integrations, and security. Changes in dy-core are manually published from `dynamicpackages` to `dynamicaviation` and `minimalizr` .
	- No direct dependencies between `dynamicpackages`, `dynamicaviation`, and `minimalizr`. They never call each other's functions, classes, or methods.
	- They don't connect to each other. Instead, they integrate together inside a single WordPress installation.
	- Shared logic lives in `dy-core`. Each app reuses functions and code from `dy-core` rather than depending on the other apps directly.
	- When adding new features: if two apps need shared behavior, put that logic in `dy-core` and have both reuse it from there — never link the apps to each other.
<empty-block/>
## Testing instructions
- Run `/Applications/MAMP/bin/startApache.sh && /Applications/MAMP/bin/startMysql.sh` to start MAMP.
- The current wordpress installation is available at `http://localhost:8888/wordpress`
- "wp-json" endpoint is available at `http://localhost:8888/wordpress/wp-json`
- Composer is installed in `/Applications/MAMP/htdocs/wordpress/wp-content/plugins/dynamicpackages`
- Run PHPStan from `/Applications/MAMP/htdocs/wordpress/wp-content/plugins/dynamicpackages` using: `vendor/bin/phpstan analyse [options] [<paths>...]` 
- Fix PHPStan errors introduced by the current changes before considering the task complete.
- Do not modify unrelated code solely to fix pre-existing PHPStan errors.
- When possible, run PHPStan only against the files or directories affected by the current task to avoid unnecessary analysis.
## Code style
- Write code that's easy to read and understand at a glance.
- Simplicity should never come at the cost of security.
- **Minimize ****`try{}catch(){}`**** usage.** Prefer safer patterns (validation, guards, default values) over wrapping everything in try/catch blocks.
- Javascript code should be es6 optimized and compatible with Jquery Slim. Javascript code should avoid render-blocking patterns. Arrow functions over standard functions. `cont` and `let` over `var`.
- PHP code suggestions/fixes/optimizations/changes should always be typed.
- Suggestions should include valid PHP 8.1 code with modern syntax
	- bracket arrays
	- spread operators
	- param typing
	- functions and methods typing
	- Protect class methods with static, private, protected and public modifiers
## **WordPress Core**
- Do not scan `wp-admin` or `wp-includes `extensively.
- For questions about functions, classes, or hooks, first consult: [https://developer.wordpress.org/reference/](https://developer.wordpress.org/reference/)
- Read local core code only when necessary to verify the behavior of the installed version.
- Limit reading to the relevant function or class.
- Do not load the entire core documentation into context.
## Polylang
- Polylang is an optional dependency.
- Do not scan `wp-admin` or `wp-includes `extensively.
- For questions about functions, classes, or hooks, first consult: [https://polylang.pro/documentation/support/developers/function-reference/](https://polylang.pro/documentation/support/developers/function-reference/) and [https://polylang.pro/documentation/support/developers/filter-reference/](https://polylang.pro/documentation/support/developers/filter-reference/)
## Coding Guards
- Think Before Coding
- Don't assume. Don't hide confusion. Surface tradeoffs.
**Before implementing:**
- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.
**Minimum code that solves the problem. Nothing speculative.**
- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.
**3. Surgical Changes:**
**Touch only what you must. Clean up only your own mess.**
When editing existing code:
- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.
When your changes create orphans:
- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.
<empty-block/>
