

const turnstileWidget1 = turnstile.render("#turnstile-container", {
	sitekey: turnstileSiteKey,
    action: 'sign-transaction',
	callback: (signTransactionToken) => {
        console.log({signTransactionToken})
    },
});
