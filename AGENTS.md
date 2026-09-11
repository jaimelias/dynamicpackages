# AGENTS.md

`dynamicpackages` is a WordPress plugin for service reservations, including hotels, tours, transport, hourly rentals, and daily rentals.

## `dy-core` folder
- This is the single source of truth for a library used by other plugins/themes.
- Do not modify or delete anything here unless explicitly requested, not even unused functions.

## Before Coding
- Implement the minimum surgical code required to solve the request correctly.
- Do not start by changing code until the relevant behavior and existing implementation are understood.
- Write simple modern code, easy to be understood.

## PHP preferences:
- Read `composer.json` for useful tools and project configs.
- Use `dy-core/security/queries.php` for handing $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER
- Write modern PHP 8.1 code
- Prefer `[]` array syntax
- Type new methods, functions and its params
- Add protection to new methods: `private`, `protected`, `public`, `static`
- Avoid unnecessary recurrent DB calls or heavy computation with `static cache`
- Avoid unnecessary use of `try{} catch(){}` with strong guards

## JS preferences:
- write modern JS code compatible with es6, jQuery Slim, arrow functions