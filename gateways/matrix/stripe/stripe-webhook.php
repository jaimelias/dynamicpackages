<?php

if ( !defined('WPINC') ) exit;

use Spipu\Html2Pdf\Html2Pdf;

#[AllowDynamicProperties]
class stripe_gateway_webhook {

    private static $cache = [];

    public function __construct($id, $mode, $sec_key, $webhook_secret) {

        // stripe listen --forward-to http://localhost:8888/wordpress/wp-json/dy-core/stripe-webhook
        // stripe trigger checkout.session.completed
        
        $this->id             = $id;
        $this->mode           = $mode;
        $this->sec_key        = $sec_key;
        $this->webhook_secret = $webhook_secret;

        add_action('rest_api_init', array($this, 'register_webhook_route'));
    }

    /* ---------- Webhook ---------- */
    public function register_webhook_route() {

        register_rest_route('dy-core', 'stripe-webhook', array(
            'methods'             => \WP_REST_Server::CREATABLE, // POST
            'callback'            => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Signature handles auth
        ));
    }

    public function handle_webhook(\WP_REST_Request $request) {

        if ($request->get_method() !== 'POST') {
            return new \WP_REST_Response(array('error' => 'Method Not Allowed'), 405);
        }

        $payload = $request->get_body();
        $sig     = $request->get_header('stripe-signature') ?: '';
        $secret  = $this->webhook_secret;

        // Hard misconfig guards (make setup failures obvious)
        if (empty($secret)) {
            write_log('Stripe webhook misconfigured: missing signing secret');
            return new \WP_REST_Response(array('error' => 'Webhook signing secret not configured'), 500);
        }

        if (empty($sig)) {
            write_log('Stripe webhook missing Stripe-Signature header');
            return new \WP_REST_Response(array('error' => 'Missing signature header'), 400);
        }

        
    
        try {

            // Verify signature + parse event
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);

            // Idempotency:
            // - done_key prevents re-processing already completed events
            // - lock_key reduces double-processing during concurrent deliveries
            $done_key = 'stripe_evt_done_' . $event->id;
            $lock_key = 'stripe_evt_lock_' . $event->id;

            if (get_transient($done_key)) {
                return new \WP_REST_Response(array('received' => true, 'duplicate' => true), 200);
            }

            if (get_transient($lock_key)) {
                return new \WP_REST_Response(array('received' => true, 'duplicate_processing' => true), 200);
            }

            set_transient($lock_key, 1, 5 * MINUTE_IN_SECONDS);

            try {

                // Only needed when you retrieve objects from Stripe (we do for Checkout Session expansions)
                if (!empty($this->sec_key)) {
                    \Stripe\Stripe::setApiKey($this->sec_key);
                }

                switch ($event->type) {

                    // Checkout Session events (Checkout payload object is a Session, not a PaymentIntent)
                    case 'checkout.session.completed':
                        write_log($event->type);
                    case 'checkout.session.async_payment_succeeded':
                    case 'checkout.session.async_payment_failed': {

                        /** @var \Stripe\Checkout\Session $sessionObj */
                        $sessionObj = $event->data->object;

                        if (empty($this->sec_key)) {
                            throw new \Exception('Stripe secret key not configured (required to retrieve session).');
                        }

                        // Retrieve the session to reliably access expanded fields
                        $session = \Stripe\Checkout\Session::retrieve($sessionObj->id, array(
                            'expand' => array('line_items', 'payment_intent'),
                        ));

                        $payment_status = $session->payment_status ?? null; // paid | unpaid | no_payment_required
                        $isPaid = in_array($payment_status, array('paid', 'no_payment_required'), true);

                        $sessionMeta  = (array) ($session->metadata ?? array());
                        $amountTotal  = $session->amount_total ?? null; // minor units
                        $currency     = $session->currency ?? null;

                        $pi      = $session->payment_intent ?? null;
                        $piMeta  = (is_object($pi) && isset($pi->metadata)) ? (array) $pi->metadata : array();

                        write_log($sessionMeta);

                        if ($event->type === 'checkout.session.async_payment_failed' || $payment_status === 'unpaid') {
                            // TODO: mark failed / notify
                            // write_log('Checkout payment failed for session ' . $session->id);
                            break;
                        }

                        if (!$isPaid) {
                            // Payment still pending (common in async methods). Ignore and wait for async_payment_succeeded.
                            break;
                        }

                        // TODO: finalize booking / mark paid exactly once
                        // Use $sessionMeta and/or $piMeta to map back to your internal order/booking id.
                        // Example keys you set during Session::create([... 'metadata' => ...])
                        // $booking_id = $sessionMeta['booking_id'] ?? null;

                       

                        break;
                    }

                    default:
                        // Ignore other events or log them
                        break;
                }

                // Mark done only after successful processing
                set_transient($done_key, 1, HOUR_IN_SECONDS);
                delete_transient($lock_key);

                return new \WP_REST_Response(array('received' => true), 200);

            } catch (\Throwable $e) {
                // Allow Stripe retries if anything failed during processing
                delete_transient($lock_key);
                throw $e;
            }

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            write_log('Stripe webhook signature invalid: ' . $e->getMessage());
            return new \WP_REST_Response(array('error' => 'Invalid signature'), 400);
        } catch (\Throwable $e) {
            write_log('Stripe webhook error: ' . $e->getMessage());
            return new \WP_REST_Response(array('error' => 'Webhook processing error'), 500);
        }
    }
}

?>
