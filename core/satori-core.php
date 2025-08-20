<?php
/*
Plugin Name: SATORI Core
Description: Foundation for SATORI plugins (bootstrap, hooks, installer client).
Version: 0.2.0
Author: Satori Graphics
Requires at least: 6.2
Requires PHP: 8.1
Update URI: https://updates.wordpressed.com.au/plugins/satori-core
*/

/* -------------------------------------------------
 * Security guard
 * -------------------------------------------------*/
defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Version constant
 * -------------------------------------------------*/
if (!defined('SATORI_CORE_VERSION')) {
    define('SATORI_CORE_VERSION', '0.2.0');
}

/* -------------------------------------------------
 * Autoload (placeholder)
 * -------------------------------------------------*/
// If using composer later, require vendor/autoload.php here.

/* -------------------------------------------------
 * Boot Core
 * -------------------------------------------------*/
add_action('plugins_loaded', static function() {
    // Register public hooks, no-ops for enterprise placeholders.
    do_action('satori/core/loaded', SATORI_CORE_VERSION);
});
