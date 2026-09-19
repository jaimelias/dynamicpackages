/* Browser events are hints; only the signed Yappy webhook settles the payment. */
(() => {
	const mount = async () => {
		const root = document.querySelector('#dy-yappy-v2');
		const config = window.dyYappyV2;
		if (!root || !config) return;

		const buttonMount = root.querySelector('#dy-yappy-v2-button');
		const button = root.querySelector('btn-yappy') || (() => {
			if (!buttonMount) return null;

			const element = document.createElement('btn-yappy');
			element.setAttribute('theme', 'blue');
			buttonMount.appendChild(element);

			return element;
		})();
		const message = root.querySelector('[role="status"]');
		const timerText = root.querySelector('#dy-yappy-v2-timer-text');
		const timerWrap = root.querySelector('#dy-yappy-v2-timer');
		let expiresAt = Number(config.expiresAt) || 0;
		let checking = false;
		let starting = false;
		let finished = false;
		let alertShown = false;
		let pollTimer;
		let countdownTimer;

		const request = async (url, method = 'GET') => {
			const endpoint = new URL(url, window.location.origin);
			const options = {
				method,
				credentials: 'same-origin',
				cache: 'no-store',
				headers: {
					'Content-Type': 'application/json',
					'X-Dy-Yappy-Token': config.token,
				},
			};

			if (method === 'GET') {
				endpoint.searchParams.set('tx_id', config.txId);
				endpoint.searchParams.set('stamp', Date.now());
			} else {
				options.body = JSON.stringify({tx_id: config.txId});
			}

			const response = await fetch(endpoint, options);
			const data = await response.json().catch(() => ({}));
			if (!response.ok || !data.success) {
				const error = new Error(data.message || config.unavailable);
				error.code = data.code || '';
				error.status = response.status;
				throw error;
			}

			return data;
		};

		const retryPayment = () => {
			if (config.retryUrl) window.location.assign(config.retryUrl);
			else window.history.back();
		};

		const showExpired = () => {
			if (alertShown) return;

			alertShown = true;
			finished = true;
			clearTimeout(pollTimer);
			clearInterval(countdownTimer);
			if (button) button.hidden = true;
			if (message) message.textContent = config.expired;

			if (typeof dyAlert === 'function') {
				dyAlert(config.expired, config.retry, retryPayment);
			} else {
				window.alert(config.expired);
				retryPayment();
			}
		};

		const renderTimer = () => {
			if (!timerText || expiresAt <= 0 || finished) return;

			const remaining = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
			const minutes = Math.floor(remaining / 60);
			const seconds = remaining % 60;
			timerText.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

			if (remaining === 0) showExpired();
		};

		const startTimer = value => {
			const parsed = Number(value) || 0;
			if (parsed <= 0) return;

			expiresAt = parsed;
			if (timerWrap) timerWrap.hidden = false;
			clearInterval(countdownTimer);
			renderTimer();
			countdownTimer = setInterval(renderTimer, 1000);
		};

		const checkStatus = async () => {
			if (checking || finished) return;

			checking = true;
			clearTimeout(pollTimer);

			try {
				const data = await request(config.checkUrl);
				if (data.retryUrl) config.retryUrl = data.retryUrl;
				if (data.expiresAt) startTimer(data.expiresAt);

				if (data.paymentStatus === 'expired') {
					showExpired();
					return;
				}

				if (data.status === 'success'
					|| ['approved', 'declined', 'cancelled', 'error'].includes(data.paymentStatus)
					|| ['declined', 'error'].includes(data.status)) {
					finished = true;
					window.location.reload();
					return;
				}

				if (['creating', 'uncertain'].includes(data.paymentStatus)) {
					if (button) button.hidden = true;
					if (message) message.textContent = config.uncertain;
				}
			} catch (error) {
				if (message) message.textContent = error.message;
				if (error.code === 'expired') showExpired();
				if (error.status === 403) {
					finished = true;
					if (button) button.hidden = true;
				}
			} finally {
				checking = false;
				if (!finished) pollTimer = setTimeout(checkStatus, 5000);
			}
		};

		if (config.paymentStatus === 'expired') {
			showExpired();
			return;
		}

		if (button) {
			button.addEventListener('eventClick', async () => {
				if (starting || finished) return;

				starting = true;
				button.isButtonLoading = true;
				if (message) message.textContent = config.waiting;

				try {
					const data = await request(config.startUrl, 'POST');
					startTimer(data.expiresAt);
					button.eventPayment(data.body);
				} catch (error) {
					if (message) message.textContent = error.message;
					if (error.code === 'expired') showExpired();
				} finally {
					starting = false;
					button.isButtonLoading = false;
					await checkStatus();
				}
			});

			const onResult = () => {
				button.isButtonLoading = false;
				if (message) message.textContent = config.waiting;
				checkStatus();
			};

			button.addEventListener('eventSuccess', onResult);
			button.addEventListener('eventError', onResult);
			button.addEventListener('isYappyOnline', event => {
				if (event.detail === false && message) message.textContent = config.unavailable;
			});
		}

		startTimer(expiresAt);
		checkStatus();

		try {
			if (!window.customElements.get('btn-yappy')) {
				await new Promise((resolve, reject) => {
					const script = document.createElement('script');
					script.type = 'module';
					script.src = config.sdkUrl;
					script.onload = resolve;
					script.onerror = reject;
					document.head.appendChild(script);
				});
			}

			await window.customElements.whenDefined('btn-yappy');
		} catch {
			if (message) message.textContent = config.unavailable;
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', mount, {once: true});
	} else {
		mount();
	}
})();
