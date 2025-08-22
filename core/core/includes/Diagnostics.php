<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Diagnostics — gather and copy site info
 * -------------------------------------------------*/
class Diagnostics {
    /** @var self|null */
    private static $instance = null;

    /* -------------------------------------------------
     * Singleton
     * -------------------------------------------------*/
    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    /* -------------------------------------------------
     * Ctor: hook AJAX endpoint
     * -------------------------------------------------*/
    private function __construct() {
        add_action('wp_ajax_satori_core_get_diagnostics', [$this, 'ajax_get_diagnostics']);
    }

    /* -------------------------------------------------
     * AJAX: return diagnostics JSON
     * -------------------------------------------------*/
    public function ajax_get_diagnostics() : void {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'satori')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if ( ! wp_verify_nonce($nonce, 'satori-core-admin') && ! wp_verify_nonce($nonce, 'satori-core-diagnostics') ) {
            wp_send_json_error(['message' => __('Bad nonce.', 'satori')], 400);
        }

        global $wp_version;
        $theme = wp_get_theme();

        $data = [
            'wp_version'          => $wp_version,
            'php_version'         => PHP_VERSION,
            'site_url'            => site_url(),
            'home_url'            => home_url(),
            'active_theme'        => sprintf('%s %s', $theme->get('Name'), $theme->get('Version')),
            'plugins'             => array_values((array) get_option('active_plugins', [])),
            'satori_core_version' => get_option('satori_core_version', 'n/a'),
            'update'              => [
                'last_check' => get_transient('satori_core_last_update_check') ?: 'n/a',
                'source'     => get_transient('satori_core_last_update_source') ?: 'n/a', // mock-json | remote | cache | manual
            ],
            'settings'            => [
                'debug'          => Helpers::get_setting('debug'),
                'update_channel' => Helpers::get_setting('update_channel'),
                'telemetry'      => Helpers::get_setting('telemetry'),
            ],
        ];

        wp_send_json_success($data);
    }
}
