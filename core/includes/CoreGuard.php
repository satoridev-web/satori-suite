<?php
namespace Satori\Shared;

/* -------------------------------------------------
 * Core Guard — Reusable Snippet v1
 * -------------------------------------------------*/
/**
 * Ensures SATORI Core is installed/active and meets minimum version.
 * Usage in modules: \Satori\Shared\CoreGuard::guard(__FILE__, '0.2.0');
 */
class CoreGuard {
    public static function guard(string $plugin_file, string $min_version): void {
        if (defined('SATORI_CORE_VERSION') && version_compare(SATORI_CORE_VERSION, $min_version, '>=')) {
            return;
        }
        add_action('admin_notices', function() use ($min_version) {
            echo '<div class="notice notice-warning"><p><strong>SATORI Core</strong> is required (≥ ' . esc_html($min_version) . '). '
               . 'Click <a href="#" id="satori-install-core">Install/Activate Core</a>.</p></div>';
        });

        // TODO: AJAX handler to install/activate Core; nonce + caps checks; multisite-aware.
    }
}
