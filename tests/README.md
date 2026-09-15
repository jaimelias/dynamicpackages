# Checkout regression checks

Run `php tests/checkout.php` from the plugin root. Each scenario runs in a fresh PHP process to isolate request caches. The suite executes the real transaction store, submission controller, gateway result renderers, conversion queue, and both confirmation classes. WordPress storage/hooks, external notifications, card validation, and the payment response are stubbed; it never charges a card or sends mail.

Coverage includes contact and estimates; Cuanto, PayPal fees, Yappy QR instructions, USDT/USDC networks; approved/declined/error/debug card results; transaction identity and destination mismatches; validation/rate-limit/lock/storage failures; repeated POSTs; GET confirmation, expired/tampered records, conversion restoration, and localized 303 redirects.

## Flow

- `Dynamicpackages_Actions` owns POST orchestration at `template_redirect`: identity, destination, transaction lock, validation, gateway preparation/execution, `dy_tx::update($tx)`, notifications, redirect.
- Gateway files retain their configuration, validation, payment execution, email filters, and result rendering. Paguelo Facil executes only through the controller's gateway hook.
- `Dy_Confirmation_Page` owns the shared endpoint, status handling, template selection, cache headers, and robots metadata. The public confirmation adapter supplies its main loop and stored package result.
- The result stores server-rendered HTML, title, excerpt, and allowed conversion fields. It does not recalculate historical prices or require POST hydration. Card credentials and Turnstile tokens are excluded.
- The old `dy_tx::update($id, $status, $payload, $ttl)` signature remains available. The object signature accepts only signed identity, status, whitelisted booking/contact data, and confirmation fields.
- A database lock serializes concurrent submissions. A persisted `processing` state blocks another charge after an interrupted request. Terminal states block repeated notifications. Notifications are attempted once: a process failure after storing the result can leave a notification undelivered and requires operational follow-up; GET never retries it.
- Conversion events are restored on GET; a one-day, HTTP-only cookie suppresses refresh duplicates in that browser. Other browsers or cleared cookies can emit the same transaction ID again.
- Validation failures stay on the existing error page. Processed card declines/errors redirect to their stored result, just like successful submissions. Records created before this change without a stored presentation use the shared endpoint's status display.
- Rewrite rules refresh once on deployment, including language-prefixed paths when Polylang is enabled.

Live WordPress/theme/Polylang rendering, real Turnstile validation, actual concurrent database connections, PDF/email delivery, and gateway sandbox responses still require integration checks in a running installation.
