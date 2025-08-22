<?php
/**
 * Plugin Name:  SATORI Core
 * Plugin URI:   https://satori.com.au/
 * Description:  Foundation for the SATORI Suite — shared settings, hooks, update client, diagnostics, and tooling.
 * Version:      0.1.0
 * Author:       SATORI
 * Author URI:   https://satori.com.au/
 * Text Domain:  satori-core
 * Domain Path:  /languages
 * Update URI:   https://updates.wordpressed.com.au
 */

// phpcs:disable WordPress.NamingConventions

namespace Satori\Core;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Constants
 * -------------------------------------------------*/
const VERSION = '0.1.0';
const MIN_PHP = '8.0';
const PLUGIN_FILE = __FILE__;
define('SATORI_CORE_PATH', plugin_dir_path(__FILE__));
define('SATORI_CORE_URL', plugin_dir_url(__FILE__));

// Make version available to modules that check for it.
if (!defined('SATORI_CORE_VERSION')) { define('SATORI_CORE_VERSION', VERSION); }

// Simple environment toggles (override via wp-config.php if needed)
if (!defined('SATORI_CORE_DEBUG')) { define('SATORI_CORE_DEBUG', false); }

/* -------------------------------------------------
 * Autoloader (PSR-4 light) — falls back if Composer isn't used yet
 * -------------------------------------------------*/
spl_autoload_register(function ($class) {
    $prefix = 'Satori\\Core\\';
    $base_dir = SATORI_CORE_PATH . 'core/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

/* -------------------------------------------------
 * Bootstrap
 * -------------------------------------------------*/
add_action('plugins_loaded', function () {
    // Minimum PHP
    if (version_compare(PHP_VERSION, MIN_PHP, '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
               . esc_html(sprintf(__('SATORI Core requires PHP %s or higher.', 'satori'), MIN_PHP))
               . '</p></div>';
        });
        return;
    }

    // Initialize singletons
    Admin\AdminSettings::instance();
    Includes\Logger::instance();
    Includes\Diagnostics::instance();
    Includes\UpdateClient::instance();
    Includes\Helpers::instance();

    do_action('satori/core/loaded');
});

/* -------------------------------------------------
 * Activation / Deactivation
 * -------------------------------------------------*/
register_activation_hook(__FILE__, function () {
    update_option('satori_core_version', VERSION);
});

register_deactivation_hook(__FILE__, function () {
    // Keep options/logs unless explicitly purged by user.
});
