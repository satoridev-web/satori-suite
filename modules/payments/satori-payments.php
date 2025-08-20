<?php
/*
Plugin Name: SATORI Payments
Description: Part of the SATORI Suite.
Version: 0.2.0
Author: Satori Graphics
Requires at least: 6.2
Requires PHP: 8.1
Update URI: https://updates.wordpressed.com.au/plugins/payments
*/

/* -------------------------------------------------
 * Security guard
 * -------------------------------------------------*/
defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Minimum Core requirement
 * -------------------------------------------------*/
const SATORI_MIN_CORE = '0.2.0';

/* -------------------------------------------------
 * CoreGuard bootstrap
 * -------------------------------------------------*/
require_once __DIR__ . '/../..//core/includes/CoreGuard.php';
\Satori\Shared\CoreGuard::guard(__FILE__, SATORI_MIN_CORE);

/* -------------------------------------------------
 * Boot plugin
 * -------------------------------------------------*/
add_action('plugins_loaded', static function() {
    if (!defined('SATORI_CORE_VERSION') || version_compare(SATORI_CORE_VERSION, SATORI_MIN_CORE, '<')) {
        return; // Core not ready yet.
    }
    // Initialize plugin services here.
});
