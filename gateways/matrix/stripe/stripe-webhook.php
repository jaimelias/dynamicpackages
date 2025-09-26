<?php
if ( !defined('WPINC') ) exit;

#[AllowDynamicProperties]
class stripe_gateway_webhook {

    private static $cache = [];

    function __construct($id, $mode, $sec_key, $webhook_secret) {

        $this->id = $id;
        $this->mode = $mode;
        $this->sec_key = $sec_key;
        $this->webhook_secret = $webhook_secret;

        // Webhook endpoint (map to a page or custom rewrite); can also use a dedicated URL:
        add_action('rest_api_init', array($this, 'register_webhook_route'));
    }

    /* ---------- Webhook ---------- */
    public function register_webhook_route() {
        register_rest_route('dy-core', '/stripe-webhook', array(
            'methods'       => \WP_REST_Server::CREATABLE, // POST
            'callback'      => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Signature handles auth
        ));
    }

    public function handle_webhook(\WP_REST_Request $request) {
        if ($request->get_method() !== 'POST') {
            return new \WP_REST_Response(array('error' => 'Method Not Allowed'), 405);
        }

        $payload = $request->get_body();
        $sig = $request->get_header('stripe-signature') ?: '';
        // If you keep separate secrets per mode, prefer that. Fallback to single option.
        $webhook_secret = $this->webhook_secret;

        try {
            \Stripe\Stripe::setApiKey($this->sec_key);
            if (!class_exists('\\Stripe\\Webhook')) {
                // require vendor if needed
            }
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $webhook_secret);

            // Idempotency guard (optional but recommended)
            $evt_key = 'stripe_evt_' . $event->id;
            if (get_transient($evt_key)) {
                return new \WP_REST_Response(array('received' => true, 'duplicate' => true), 200);
            }
            set_transient($evt_key, 1, HOUR_IN_SECONDS);

            switch ($event->type) {
                case 'checkout.session.completed': {
                    /** @var \Stripe\Checkout\Session $sessionObj */
                    $sessionObj = $event->data->object;

                    // Session-level metadata (you set this in Session::create([... 'metadata' => $metadata ]))
                    $sessionMeta = (array) ($sessionObj->metadata ?? array());

                    // Retrieve with expansions for amounts, PI, and line_items
                    $session = \Stripe\Checkout\Session::retrieve($sessionObj->id, [
                        'expand' => ['line_items', 'payment_intent']
                    ]);

                    $isPaid = ($session->payment_status === 'paid');
                    $amountTotal= $session->amount_total;   // in minor units
                    $currency = $session->currency;       // e.g. 'usd'
                    $piMeta = (array) ($session->payment_intent->metadata ?? array());

                    // TODO: finalize your booking here (mark paid exactly once)
                    // Use $sessionMeta / $piMeta to map back to your order.
                    // Example:
                    // dy_orders::mark_paid($sessionMeta['order_id'] ?? null, $session->id, $amountTotal, $currency);

                    break;
                }

                case 'checkout.session.async_payment_succeeded':
                case 'payment_intent.succeeded': {
                    // If you also rely on PI events:
                    /** @var \Stripe\PaymentIntent $pi */
                    $pi = $event->data->object;
                    $piMeta = (array) ($pi->metadata ?? array());
                    // TODO: optional: mark paid by PI
                    break;
                }

                case 'checkout.session.async_payment_failed':
                case 'payment_intent.payment_failed': {
                    // TODO: mark failed / notify
                    break;
                }

                // Optional post-purchase lifecycle sync
                case 'charge.refunded':
                case 'charge.dispute.created':
                    // TODO: reflect refunds/disputes
                    break;

                default:
                    // Ignore other events or log them
                    break;
            }

            return new \WP_REST_Response(array('received' => true), 200);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            write_log('Stripe webhook signature invalid: ' . $e->getMessage());
            return new \WP_REST_Response(array('error' => 'Invalid signature'), 400);
        } catch (\Exception $e) {
            write_log('Stripe webhook error: ' . $e->getMessage());
            return new \WP_REST_Response(array('error' => 'Webhook processing error'), 500);
        }
    }

}

?>