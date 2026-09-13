window.dyTurnstileWaiters = window.dyTurnstileWaiters || {};

const notifyTurnstile = (widgetId, type, value) => {
	const waiter = window.dyTurnstileWaiters[widgetId];

	if (!waiter) {
		return;
	}

	delete window.dyTurnstileWaiters[widgetId];

	if (type === 'resolve') {
		waiter.resolve(value);
	} else {
		waiter.reject(value);
	}
};

const turnstileWidget1 = turnstile.render('#turnstile-container-1', {
	sitekey: turnstileSiteKey,
	action: 'sign-transaction',
	appearance: 'execute',
	callback: token => notifyTurnstile(turnstileWidget1, 'resolve', token),
	'expired-callback': () =>
		notifyTurnstile(
			turnstileWidget1,
			'reject',
			new Error('Turnstile token expired.')
		),
	'error-callback': code =>
		notifyTurnstile(
			turnstileWidget1,
			'reject',
			new Error(`Turnstile error: ${code}`)
		)
});

const turnstileWidget2 = turnstile.render('#turnstile-container-2', {
	sitekey: turnstileSiteKey,
	action: 'submit-transaction',
	appearance: 'execute',
	callback: token => notifyTurnstile(turnstileWidget2, 'resolve', token),
	'expired-callback': () =>
		notifyTurnstile(
			turnstileWidget2,
			'reject',
			new Error('Turnstile token expired.')
		),
	'error-callback': code =>
		notifyTurnstile(
			turnstileWidget2,
			'reject',
			new Error(`Turnstile error: ${code}`)
		)
});