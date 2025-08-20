<?php
namespace Satori\Shared;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * CoreGuard — For SATORI Modules to ensure Core is available
 * -------------------------------------------------*/
class CoreGuard {
    /**
     * Guard loader
     *
     * @param string $caller_file   Absolute path of the module file calling this guard.
     * @param string $min_core_ver  Minimum required SATORI Core version.
     */
    public static function guard(string $caller_file, string $min_core_ver) : void {
        // If Core is already loaded, verify version
        if (defined('SATORI_CORE_PATH')) {
            $loaded = get_option('satori_core_version', '0.0.0');
            if (version_compare($loaded, $min_core_ver, '>=')) {
                return;
            }
        }

        // Not loaded or too old — show admin notice with an installer link
        if (is_admin()) {
            add_action('admin_notices', function() use ($min_core_ver) {
                $nonce = wp_create_nonce('satori_coreguard_install');
                $url   = add_query_arg([
                    'satori_coreguard_install' => 1,
                    '_wpnonce' => $nonce,
                ], admin_url('plugins.php'));

                echo '<div class="notice notice-warning"><p>' .
                    esc_html__('SATORI Core is required by a SATORI module. Click to install/activate.', 'satori-core') .
                    ' <a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Install/Activate Core', 'satori-core') . '</a>' .
                    '</p></div>';
            });

            add_action('admin_init', function() use ($min_core_ver) {
                if (!isset($_GET['satori_coreguard_install'])) { return; }
                if (!current_user_can('install_plugins')) { return; }
                check_admin_referer('satori_coreguard_install');

                // Attempt to activate if present
                $plugin_file = 'satori-core/satori-core.php';
                if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                    activate_plugin($plugin_file);
                    return;
                }

                // Otherwise, try install from WordPress.org slug placeholder (future: private repo or zip)
                // For MVP we just display an error
                wp_die(esc_html__('SATORI Core is not found. Please upload the satori-core plugin zip and activate it.', 'satori-core'));
            });
        }
    }
}
