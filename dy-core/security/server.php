<?php

if(!function_exists('get_cloudflare_proxy_ranges')) {
    function get_cloudflare_proxy_ranges() : array {
        return apply_filters('dy_cloudflare_proxy_ranges', [
			'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
			'141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
			'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
			'104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32',
			'2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
			'2a06:98c0::/29', '2c0f:f248::/32'
		]);
    }
}

if(!function_exists('secure_server')) {

    function secure_server( string $key )  : string {

        if($key === '') {
            return '';
        }

        static $cache = [];
        $cache_key = 'secure_server_' . $key;

        if(array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
        }


        $value = $_SERVER[ $key ] ?? '';

        return $cache[$cache_key] = is_scalar( $value )
            ? trim( (string) wp_unslash( $value ) )
            : '';
    };

}

if ( ! function_exists( 'get_request_country_code' ) ) {
	/**
	 * Get the visitor country code provided by Cloudflare.
	 *
	 * @return string ISO 3166-1 alpha-2 country code, "XX", "T1", or an empty string.
	 */
	function get_request_country_code(): string {

		$value = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$country = strtoupper( trim( (string) $value ) );

		return preg_match( '/^[A-Z0-9]{2}$/', $country )
			? $country
			: '';
	}
}

if(!function_exists('get_ip_address'))
{
	/**
	 * Return the normalized client IP, or an empty string if it cannot be trusted.
	 */
	function get_ip_address(): string
	{
		$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

		if(!filter_var($ip, FILTER_VALIDATE_IP)) {
			return '';
		}

		// Match the proxy policy used by cloudflare_ban_ip_address().
		$proxy_ranges = get_cloudflare_proxy_ranges();
		$packed_ip = inet_pton($ip);

		foreach($proxy_ranges as $cidr) {
			$parts = explode('/', (string) $cidr, 2);

			if(count($parts) !== 2 || !filter_var($parts[0], FILTER_VALIDATE_IP) || !ctype_digit($parts[1])) {
				continue;
			}

			$network = inet_pton($parts[0]);
			$bits = (int) $parts[1];

			if(strlen($network) !== strlen($packed_ip) || $bits > strlen($packed_ip) * 8) {
				continue;
			}

			$bytes = intdiv($bits, 8);
			$remaining = $bits % 8;

			if(substr($packed_ip, 0, $bytes) !== substr($network, 0, $bytes)) {
				continue;
			}

			if($remaining > 0) {
				$mask = (0xff << (8 - $remaining)) & 0xff;

				if((ord($packed_ip[$bytes]) & $mask) !== (ord($network[$bytes]) & $mask)) {
					continue;
				}
			}

			$ip = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));

			if(!filter_var($ip, FILTER_VALIDATE_IP)) {
				return '';
			}

			$packed_ip = inet_pton($ip);
			break;
		}

		return inet_ntop($packed_ip);
	}
	
}


if(!function_exists('current_url_full')) {
	function current_url_full(): string
	{
		// Detect scheme (https/http), considering reverse proxies.
		$isHttps = (
			(!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
			|| (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
			|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
		);
		$scheme = $isHttps ? 'https' : 'http';

		// Determine host (prefer proxy header if present; take the first value).
		$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
		if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$xfh  = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			$host = trim($xfh[0]); // Use the left-most host
		}

		// Extract hostname and port if HTTP_HOST already includes a port.
		$hostname = $host;
		$hostPort = null;
		if (strpos($host, ':') !== false) {
			[$hostname, $maybePort] = explode(':', $host, 2);
			if (ctype_digit($maybePort)) {
				$hostPort = (int)$maybePort;
			}
		}

		// Prefer forwarded port if supplied.
		if (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && ctype_digit($_SERVER['HTTP_X_FORWARDED_PORT'])) {
			$hostPort = (int)$_SERVER['HTTP_X_FORWARDED_PORT'];
		} elseif (empty($hostPort) && !empty($_SERVER['SERVER_PORT']) && ctype_digit((string)$_SERVER['SERVER_PORT'])) {
			$hostPort = (int)$_SERVER['SERVER_PORT'];
		}

		// Omit default ports.
		$defaultPort = $isHttps ? 443 : 80;
		$portPart    = ($hostPort && $hostPort !== $defaultPort) ? ':' . $hostPort : '';

		// Request URI (path + query + fragment if present).
		$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

		$url = $scheme . '://' . $hostname . $portPart . $requestUri;

		// If WordPress is loaded, return an escaped version.
		if (function_exists('esc_url_raw')) {
			return esc_url_raw($url);
		}

		// Plain PHP: lightly validate/sanitize.
		return filter_var($url, FILTER_SANITIZE_URL) ?: $url;
	}
}

?>