<?php
namespace Satori\Core\Admin;

use Satori\Core\Includes\Helpers;
use Satori\Core\Includes\Logger;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Admin Settings — Tools/Settings UI + Debug Footer + Utilities
 * -------------------------------------------------*/
class AdminSettings {
    private static $instance = null;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        add_action('admin_menu',               [$this, 'register_menu']);
        add_action('admin_init',               [$this, 'register_settings']);
        add_action('admin_enqueue_scripts',    [$this, 'assets']);

        // Render inside #wpfooter (prevents overlap behind admin menu)
        add_action('in_admin_footer',          [$this, 'render_debug_footer']);

        // Advanced utilities (AJAX)
        add_action('wp_ajax_satori_core_recheck_updates',  [$this, 'ajax_recheck_updates']);
        add_action('wp_ajax_satori_core_clear_caches',     [$this, 'ajax_clear_caches']);
        add_action('wp_ajax_satori_core_export_settings',  [$this, 'ajax_export_settings']);
        add_action('wp_ajax_satori_core_view_log',         [$this, 'ajax_view_log']);

        // Log settings updates
        add_action('update_option_satori_core_settings',   [$this, 'on_settings_updated'], 10, 3);
        add_action('add_option_satori_core_settings',      [$this, 'on_settings_added'], 10, 2);
    }

    /* -------------------------------------------------
     * Menu
     * -------------------------------------------------*/
    public function register_menu() : void {
        add_menu_page(
            __('SATORI', 'satori-core'),
            __('SATORI', 'satori-core'),
            'manage_options',
            'satori-tools',
            [$this, 'render_page'],
            'dashicons-admin-generic',
            58
        );
    }

    /* -------------------------------------------------
     * Assets
     * -------------------------------------------------*/
    public function assets() : void {
        $ver = defined('\Satori\Core\VERSION') ? \Satori\Core\VERSION : '0.1.0';

        // Expect constants like SATORI_CORE_URL to be defined by the bootstrap.
        wp_enqueue_style('satori-core-admin', SATORI_CORE_URL . 'core/assets/css/admin.css', [], $ver);
        wp_enqueue_script('satori-core-admin', SATORI_CORE_URL . 'core/assets/js/admin.js', ['jquery'], $ver, true);

        wp_localize_script('satori-core-admin', 'SatoriCoreAdmin', [
            'nonce' => wp_create_nonce('satori-core-admin'),
        ]);
    }

    /* -------------------------------------------------
     * Settings API
     * -------------------------------------------------*/
    public function register_settings() : void {
        register_setting('satori_core', 'satori_core_settings', [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default'           => [
                'site_id'        => '',
                'update_channel' => 'stable',
                'license_key'    => '',
                'debug'          => false,
                'telemetry'      => false,
            ],
        ]);

        // General
        add_settings_section('satori_core_general', __('General', 'satori-core'), '__return_false', 'satori_core');
        add_settings_field('site_id',         __('Site ID', 'satori-core'),        [$this, 'field_site_id'],        'satori_core', 'satori_core_general', ['key' => 'site_id']);
        add_settings_field('update_channel',  __('Updates Channel', 'satori-core'),[$this, 'field_update_channel'], 'satori_core', 'satori_core_general', ['key' => 'update_channel']);
        add_settings_field('license_key',     __('License Key', 'satori-core'),    [$this, 'field_license_key'],    'satori_core', 'satori_core_general', ['key' => 'license_key']);

        // Debug & Telemetry
        add_settings_section('satori_core_debug', __('Debug & Telemetry', 'satori-core'), '__return_false', 'satori_core');
        add_settings_field('debug',      __('Debug Mode', 'satori-core'),       [$this, 'field_debug'],     'satori_core', 'satori_core_debug', ['key' => 'debug']);
        add_settings_field('telemetry',  __('Telemetry (opt‑in)', 'satori-core'),[$this, 'field_telemetry'],'satori_core', 'satori_core_debug', ['key' => 'telemetry']);

        // Let modules register additional tabs/fields
        do_action('satori/core/register_settings_tabs');
    }

    public function sanitize_settings($input) : array {
        $out = [];
        $out['site_id']        = sanitize_text_field($input['site_id'] ?? '');
        $out['update_channel'] = in_array(($input['update_channel'] ?? 'stable'), ['stable','beta'], true) ? $input['update_channel'] : 'stable';
        $out['license_key']    = sanitize_text_field($input['license_key'] ?? '');
        $out['debug']          = !empty($input['debug']);
        $out['telemetry']      = !empty($input['telemetry']);
        return $out;
    }

    private function get_opts() : array {
        return get_option('satori_core_settings', []);
    }

    /* -------------------------------------------------
     * Field renderers (WP default tables + descriptions)
     * -------------------------------------------------*/
    public function field_site_id($args) : void {
        $opts = $this->get_opts();
        $key  = esc_attr($args['key']);
        $val  = esc_attr($opts[$key] ?? '');
        echo '<input type="text" class="regular-text" name="satori_core_settings['.$key.']" value="'.$val.'" readonly />';
        echo '<p class="description">'.esc_html__('A unique identifier for this site. Read‑only.', 'satori-core').'</p>';
    }

    public function field_update_channel($args) : void {
        $opts = $this->get_opts();
        $key  = esc_attr($args['key']);
        $val  = esc_attr($opts[$key] ?? 'stable');

        echo '<select name="satori_core_settings['.$key.']">';
        echo '<option value="stable" '.selected($val, 'stable', false).'>'.esc_html__('Stable (recommended)', 'satori-core').'</option>';
        echo '<option value="beta" '.selected($val, 'beta', false).'>'.esc_html__('Beta (early features)', 'satori-core').'</option>';
        echo '</select>';
        echo '<p class="description">'.esc_html__('Choose which release channel to receive updates from. Stable is tested and production‑ready. Beta includes new features earlier and may be less stable.', 'satori-core').'</p>';
    }

    public function field_license_key($args) : void {
        $opts = $this->get_opts();
        $key  = esc_attr($args['key']);
        $val  = esc_attr($opts[$key] ?? '');
        echo '<input type="text" class="regular-text" name="satori_core_settings['.$key.']" value="'.$val.'" />';
        echo '<p class="description">'.esc_html__('Optional for MVP. Stored for future PRO/Enterprise licensing, not enforced yet.', 'satori-core').'</p>';
    }

    public function field_debug($args) : void {
        $opts = $this->get_opts();
        $key  = esc_attr($args['key']);
        $val  = !empty($opts[$key]);
        echo '<label><input type="checkbox" name="satori_core_settings['.$key.']" value="1" '.checked($val, true, false).' /> '.esc_html__('Enabled', 'satori-core').'</label>';
        echo '<p class="description">'.esc_html__('Enables verbose logging and shows a compact debug footer in WP admin. Only administrators can see debug info.', 'satori-core').'</p>';
    }

    public function field_telemetry($args) : void {
        $opts = $this->get_opts();
        $key  = esc_attr($args['key']);
        $val  = !empty($opts[$key]);
        echo '<label><input type="checkbox" name="satori_core_settings['.$key.']" value="1" '.checked($val, true, false).' /> '.esc_html__('Opt‑in', 'satori-core').'</label>';
        echo '<p class="description">'.esc_html__('Sends anonymous technical information (e.g., WordPress version, PHP version, active SATORI modules). No personal data is collected. OFF by default in MVP.', 'satori-core').'</p>';
    }

    /* -------------------------------------------------
     * Page render (WP default tables + default buttons)
     * -------------------------------------------------*/
    public function render_page() : void {
        if (!current_user_can('manage_options')) return;

        $nonce_recheck = wp_create_nonce('satori_core_recheck_updates');
        $nonce_clear   = wp_create_nonce('satori_core_clear_caches');
        $nonce_export  = wp_create_nonce('satori_core_export_settings');
        $ver           = defined('\Satori\Core\VERSION') ? \Satori\Core\VERSION : '0.1.0';
        ?>
        <div class="wrap satori-core-wrap">
            <h1><?php echo esc_html__('SATORI — Tools/Settings', 'satori-core'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('satori_core'); ?>
                <table class="form-table" role="presentation">
                    <?php do_settings_sections('satori_core'); ?>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr/>

            <h2><?php echo esc_html__('Advanced', 'satori-core'); ?></h2>
            <p class="description"><?php echo esc_html__('Utilities for maintenance and diagnostics. Safe actions only; admin capability required.', 'satori-core'); ?></p>
            <p>
                <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=satori_core_recheck_updates&_wpnonce=' . $nonce_recheck) ); ?>" class="button"><?php echo esc_html__('Recheck Updates', 'satori-core'); ?></a>
                <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=satori_core_clear_caches&_wpnonce=' . $nonce_clear) ); ?>" class="button"><?php echo esc_html__('Clear SATORI Caches/Transients', 'satori-core'); ?></a>
                <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=satori_core_export_settings&_wpnonce=' . $nonce_export) ); ?>" class="button"><?php echo esc_html__('Export Settings (JSON)', 'satori-core'); ?></a>
            </p>

            <h2><?php echo esc_html__('Diagnostics', 'satori-core'); ?></h2>
            <p>
                <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin-ajax.php?action=satori_core_copy_diagnostics'), 'satori_core_diag') ); ?>" class="button"><?php echo esc_html__('Copy Diagnostics to Clipboard', 'satori-core'); ?></a>
            </p>

            <div class="satori-core-footer">
                <?php if (Helpers::get_setting('debug')): ?>
                    <span class="tag"><?php echo esc_html__('Debug ON', 'satori-core'); ?></span>
                <?php endif; ?>
                <span><?php echo esc_html__('Version', 'satori-core'); ?>: <?php echo esc_html($ver); ?></span>
            </div>
        </div>
        <?php
    }

    /* -------------------------------------------------
     * Debug Footer (admins only, when Debug ON) — INSIDE #wpfooter
     * -------------------------------------------------*/
    public function render_debug_footer() : void {
        if ( ! is_admin() || ! current_user_can('manage_options') || ! Helpers::get_setting('debug') ) return;

        $ver        = defined('\Satori\Core\VERSION') ? \Satori\Core\VERSION : '0.1.0';
        $nonce_view = wp_create_nonce('satori_core_view_log');
        $view_url   = admin_url('admin-ajax.php?action=satori_core_view_log&_wpnonce=' . $nonce_view);

        echo '<div class="satori-core-debug-footer">';
        echo    '<span class="label">'. esc_html__('SATORI Core', 'satori-core') . ' v' . esc_html($ver) . '</span>';
        echo    '<span class="sep">|</span>';
        echo    '<span class="state">'. esc_html__('Debug:', 'satori-core') . ' ' . esc_html__('ON', 'satori-core') . '</span>';
        echo    '<span class="sep">|</span>';
        echo    '<a class="log-link" href="'. esc_url($view_url) .'" target="_blank" rel="noopener noreferrer">'. esc_html__("View today's log", 'satori-core') .'</a>';
        echo '</div>';
    }

    /* -------------------------------------------------
     * AJAX: Recheck updates
     * -------------------------------------------------*/
    public function ajax_recheck_updates() : void {
        check_ajax_referer('satori_core_recheck_updates');
        if (!current_user_can('manage_options')) { wp_die(-1); }

        Logger::writeStatic('info', 'Manual update recheck requested');

        // Clear WP plugin update caches and our own
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }
        delete_site_transient('update_plugins');
        delete_transient('update_plugins');

        // Trigger a fresh check
        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }

        Logger::writeStatic('info', 'Manual update recheck completed');
        wp_safe_redirect( wp_get_referer() ?: admin_url('admin.php?page=satori-tools') );
        exit;
    }

    /* -------------------------------------------------
     * AJAX: Clear caches/transients (IMPROVED)
     *  - Clears BOTH site and non-site transients
     *  - Clears WordPress' update_plugins caches
     *  - Flushes object cache if present
     * -------------------------------------------------*/
    public function ajax_clear_caches() : void {
        check_ajax_referer('satori_core_clear_caches');
        if (!current_user_can('manage_options')) { wp_die(-1); }

        Logger::writeStatic('info', 'Clearing SATORI + WP update transients');

        global $wpdb;

        // 1) Delete our OWN transients (site + non-site)
        // note: options table stores transients as option_name LIKE patterns
        $patterns = [
            // Generic SATORI prefixes (belt & braces)
            '_transient_satori_%',
            '_transient_timeout_satori_%',
            '_site_transient_satori_%',
            '_site_transient_timeout_satori_%',

            // Explicit UpdateClient payload key (if used)
            '_transient_satori_core_update_payload',
            '_transient_timeout_satori_core_update_payload',
            '_site_transient_satori_core_update_payload',
            '_site_transient_timeout_satori_core_update_payload',
        ];

        foreach ($patterns as $like) {
            // Some patterns include % already; prepare is still fine
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $like
                )
            );
        }

        // 2) Clear WordPress' own plugin update caches (site + non-site)
        delete_site_transient('update_plugins');
        delete_transient('update_plugins');
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }

        // 3) Optional: flush object cache (if persistent cache is active)
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        Logger::writeStatic('info', 'Cleared SATORI + WP update transients');

        wp_safe_redirect( wp_get_referer() ?: admin_url('admin.php?page=satori-tools') );
        exit;
    }

    /* -------------------------------------------------
     * AJAX: Export settings (JSON download)
     * -------------------------------------------------*/
    public function ajax_export_settings() : void {
        check_ajax_referer('satori_core_export_settings');
        if (!current_user_can('manage_options')) { wp_die(-1); }

        Logger::writeStatic('info', 'Exporting SATORI Core settings');

        $settings = get_option('satori_core_settings', []);
        $json     = wp_json_encode($settings, JSON_PRETTY_PRINT);

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=satori-core-settings.json');
        echo $json;
        exit;
    }

    /* -------------------------------------------------
     * AJAX: Securely stream today's log (admins only)
     * -------------------------------------------------*/
    public function ajax_view_log() : void {
        check_ajax_referer('satori_core_view_log');
        Logger::streamLatestLogStatic(); // handles caps + headers + exit
    }

    /* -------------------------------------------------
     * Settings change logging
     * -------------------------------------------------*/
    public function on_settings_updated($old_value, $value, $option) : void {
        $masked = $value;
        if (!empty($masked['license_key'])) { $masked['license_key'] = '***'; }
        Logger::writeStatic('info', 'Settings updated', $masked);
    }

    public function on_settings_added($option, $value) : void {
        $masked = $value;
        if (!empty($masked['license_key'])) { $masked['license_key'] = '***'; }
        Logger::writeStatic('info', 'Settings added', $masked);
    }
}
