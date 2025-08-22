<?php
namespace Satori\Core\Admin;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * SATORI Core – Admin Hooks
 * -------------------------------------------------*/
class Admin {

    public function __construct() {
        // Settings page
        new AdminSettings();

        // Hook tools
        add_action('admin_post_satori_recheck_updates', [$this, 'recheck_updates']);
        add_action('admin_post_satori_clear_caches', [$this, 'clear_caches']);
        add_action('admin_post_satori_export_settings', [$this, 'export_settings']);

        // Show transient notices
        add_action('admin_notices', [$this, 'show_notices']);
    }

    private function add_notice(string $msg, string $type = 'success') {
        set_transient('satori_core_notice', ['msg'=>$msg, 'type'=>$type], 30);
    }

    public function show_notices() {
        if ($notice = get_transient('satori_core_notice')) {
            delete_transient('satori_core_notice');
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['msg'])
            );
        }
    }

    public function recheck_updates() {
        do_action('satori_core_check_updates', ['source'=>'manual']);
        $this->add_notice(__('Update check triggered.', 'satori-core'));
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    public function clear_caches() {
        do_action('satori_core_clear_caches');
        $this->add_notice(__('SATORI caches cleared.', 'satori-core'));
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    public function export_settings() {
        $settings = get_option('satori_core_settings', []);
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="satori-core-settings.json"');
        echo wp_json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
}
