<?php

if (!defined('WPINC')) exit;

/** Package presentation for the endpoint registered by Dy_Confirmation_Page. */
class Dynamicpackages_Confirmation_Page
{
    private string $version;
    private ?object $tx = null;

    public function __construct(int|string $version)
    {
        $this->version = (string) $version;
        //add_filter('posts_pre_query', [$this, 'transaction_post'], 10, 2);
    }

    public static function is_confirmation(): bool
    {
        return secure_server('REQUEST_METHOD') === 'GET'
            && !is_admin() && !wp_doing_ajax() && !wp_doing_cron()
            && !(defined('REST_REQUEST') && REST_REQUEST)
            && is_string(get_query_var('dy-tx')) && get_query_var('dy-tx') !== '';
    }
}
