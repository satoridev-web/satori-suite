<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Diagnostics — gather and copy site info
 * -------------------------------------------------*/
class Diagnostics {
    private static $instance = null;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        add_action('wp_ajax_satori_core_copy_diagnostics', [$this, 'ajax_copy']);
    }

    public function ajax_copy() : void {
        check_ajax_referer('satori_core_diag');
        if (!current_user_can('manage_options')) { wp_send_json_error('forbidden', 403); }

        $data = [
            'wp' => get_bloginfo('version'),
            'php' => PHP_VERSION,
            'locale' => get_locale(),
            'site_url' => site_url(),
            'home_url' => home_url(),
            'active_theme' => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
            'plugins' => array_keys(get_option('active_plugins', [])),
            'satori_core_version' => get_option('satori_core_version', 'n/a'),
            'settings' => [
                'debug' => Helpers::get_setting('debug'),
                'update_channel' => Helpers::get_setting('update_channel'),
                'telemetry' => Helpers::get_setting('telemetry'),
            ]
        ];

        wp_send_json_success($data);
    }
}
