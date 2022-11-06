<?php

if ( !defined( 'WPINC' ) ) exit;

$plugin_dir_path = plugin_dir_path( __FILE__ );

if(!defined('DY_CORE_FUNCTIONS'))
{
    require_once $plugin_dir_path . 'functions.php';
}

if(!class_exists('Sendgrid_Mailer'))
{
    require_once $plugin_dir_path . 'sendgrid.php';
}

if(!class_exists('Dynamic_Core_Admin'))
{
    require_once $plugin_dir_path . 'admin.php';
}

if(!class_exists('Dynamic_Core_Public'))
{
    require_once $plugin_dir_path . 'public.php';
}


?>