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
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue' ]);

        // Debug footer
        add_action('in_admin_footer', [ $this, 'debug_footer' ], 99);
        add_action('admin_footer',    [ $this, 'debug_footer' ], 99);

        // Utilities
        add_action('wp_ajax_satori_core_recheck_updates',   [ $this, 'ajax_recheck_updates' ]);
        add_action('wp_ajax_satori_core_clear_caches',      [ $this, 'ajax_clear_caches' ]);
        add_action('wp_ajax_satori_core_export_settings',   [ $this, 'ajax_export_settings' ]);
        add_action('wp_ajax_satori_core_write_test_log',    [ $this, 'ajax_write_test_log' ]);

        // Streaming endpoint
        add_action('wp_ajax_satori_core_stream_latest_log', [ $this, 'ajax_stream_latest_log' ]);
    }

    public function register_menu() : void {
        add_options_page(
            __('SATORI Settings','satori'),
            __('SATORI','satori'),
            'manage_options',
            'satori-core-settings',
            [ $this, 'render_page' ]
        );
    }

    public function register_settings() : void {
        register_setting('satori_core', 'satori_core_settings', [
            'sanitize_callback' => [ $this, 'sanitize' ],
        ]);
    }

    public function enqueue($hook) : void {
        if ($hook !== 'settings_page_satori-core-settings') return;

        $ver = defined('SATORI_CORE_VERSION') ? SATORI_CORE_VERSION : 'dev';
        wp_enqueue_style('satori-core-admin', SATORI_CORE_URL . 'core/assets/css/admin.css', [], $ver);
        wp_enqueue_script('satori-core-admin', SATORI_CORE_URL . 'core/assets/js/admin.js', ['jquery'], $ver, true);

        $stream_url = wp_nonce_url(
            admin_url('admin-ajax.php?action=satori_core_stream_latest_log'),
            'satori-core-admin'
        );

        wp_localize_script('satori-core-admin', 'SatoriCoreAdmin', [
            'nonce'        => wp_create_nonce('satori-core-admin'),
            'ajaxurl'      => admin_url('admin-ajax.php'),
            'streamLogUrl' => $stream_url,
        ]);
    }

    public function sanitize($input) : array {
        $existing = get_option('satori_core_settings', []);
        $in = is_array($input) ? $input : [];
        $out = $existing;

        if (array_key_exists('update_channel', $in)) {
            $out['update_channel'] = sanitize_text_field($in['update_channel']);
        } elseif (!isset($out['update_channel'])) {
            $out['update_channel'] = 'stable';
        }

        if (array_key_exists('license', $in)) {
            $out['license'] = sanitize_text_field($in['license']);
        } elseif (!isset($out['license'])) {
            $out['license'] = '';
        }

        if (array_key_exists('debug', $in)) {
            $out['debug'] = ! empty($in['debug']) ? '1' : '0';
        } elseif (!isset($out['debug'])) {
            $out['debug'] = '0';
        }

        if (array_key_exists('telemetry', $in)) {
            $out['telemetry'] = ! empty($in['telemetry']) ? '1' : '0';
        } elseif (!isset($out['telemetry'])) {
            $out['telemetry'] = '0';
        }

        return $out;
    }

    public function render_page() : void {
        if (! current_user_can('manage_options')) return;

        $active   = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        $settings = get_option('satori_core_settings', []);
        $tabs = [
            'general'  => __('General','satori'),
            'debug'    => __('Debug & Telemetry','satori'),
            'advanced' => __('Advanced','satori'),
        ];

        $home    = home_url('/');
        $site_id = hash_hmac('sha256', $home, wp_salt('auth'));
        ?>
        <div class="wrap satori-core-wrap">
            <h1><?php echo esc_html__('SATORI Core','satori'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $key => $label) : ?>
                    <a class="nav-tab <?php echo $active === $key ? 'nav-tab-active' : ''; ?>"
                       href="<?php echo esc_url( add_query_arg('tab', $key, menu_page_url('satori-core-settings', false)) ); ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields('satori_core'); ?>

                <?php if ($active === 'general') : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Site ID','satori'); ?>
                                <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Unique identifier generated from your site URL.','satori'); ?>">?</span>
                            </th>
                            <td><code><?php echo esc_html($site_id); ?></code></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Updates channel','satori'); ?>
                                <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Select Stable (recommended) for production, or Beta for testing new features early.','satori'); ?>">?</span>
                            </th>
                            <td>
                                <select name="satori_core_settings[update_channel]">
                                    <option value="stable" <?php selected(($settings['update_channel'] ?? 'stable'), 'stable'); ?>><?php esc_html_e('Stable','satori'); ?></option>
                                    <option value="beta"   <?php selected(($settings['update_channel'] ?? 'stable'), 'beta'); ?>><?php esc_html_e('Beta','satori'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('License key','satori'); ?>
                                <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Enter your license key to enable updates and support.','satori'); ?>">?</span>
                            </th>
                            <td><input type="text" class="regular-text" name="satori_core_settings[license]" value="<?php echo esc_attr($settings['license'] ?? ''); ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>

                <?php elseif ($active === 'debug') : ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Debug mode','satori'); ?>
                                <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Enable detailed logging and show a debug footer on all admin pages.','satori'); ?>">?</span>
                            </th>
                            <td>
                                <input type="hidden" name="satori_core_settings[debug]" value="0">
                                <label>
                                    <input type="checkbox" name="satori_core_settings[debug]" value="1" <?php checked(($settings['debug'] ?? '0'), '1'); ?>>
                                    <?php esc_html_e('Enable verbose logging and admin debug footer','satori'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Telemetry','satori'); ?>
                                <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Opt-in to anonymous usage data that helps us improve SATORI. Default is OFF.','satori'); ?>">?</span>
                            </th>
                            <td>
                                <input type="hidden" name="satori_core_settings[telemetry]" value="0">
                                <label>
                                    <input type="checkbox" name="satori_core_settings[telemetry]" value="1" <?php checked(($settings['telemetry'] ?? '0'), '1'); ?>>
                                    <?php esc_html_e('Opt-in to anonymous usage metrics (OFF by default)','satori'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="button" id="satori-copy-diagnostics" class="button">
                            <?php esc_html_e('Copy diagnostics to clipboard','satori'); ?>
                        </button>
                        <span id="satori-copy-diagnostics-status" class="description" aria-live="polite"></span>
                    </p>

                    <p>
                        <button type="button" id="satori-write-test-log" class="button">
                            <?php esc_html_e('Write test log entry','satori'); ?>
                        </button>
                        <span id="satori-test-log-status" class="description" aria-live="polite"></span>
                    </p>

                    <?php submit_button(); ?>

                <?php else : ?>
                    <p class="description"><?php esc_html_e('Maintenance actions','satori'); ?></p>
                    <p class="satori-advanced-actions">
                        <button type="button" id="satori-recheck-updates" class="button">
                            <?php esc_html_e('Recheck updates','satori'); ?>
                        </button>
                        <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Force WordPress to immediately recheck available plugin updates.','satori'); ?>">?</span>

                        <button type="button" id="satori-clear-caches"  class="button">
                            <?php esc_html_e('Clear SATORI caches','satori'); ?>
                        </button>
                        <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Remove cached update payloads and transients. Safe to run anytime.','satori'); ?>">?</span>

                        <button type="button" id="satori-export-settings" class="button">
                            <?php esc_html_e('Export settings (JSON)','satori'); ?>
                        </button>
                        <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Download your current SATORI settings in JSON format for backup or migration.','satori'); ?>">?</span>

                        <button type="button" id="satori-view-log" class="button">
                            <?php esc_html_e('View latest log','satori'); ?>
                        </button>
                        <span class="satori-tooltip" data-tooltip="<?php esc_attr_e('Open the most recent SATORI log file in a new browser tab.','satori'); ?>">?</span>

                        <span id="satori-advanced-status" class="description" aria-live="polite"></span>
                    </p>
                    <?php submit_button(); ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /* -------------------------------------------------
     * Debug footer
     * -------------------------------------------------*/
    public function debug_footer() : void {
        static $printed = false;
        if ($printed) { return; }
        if (! current_user_can('manage_options')) { return; }

        $debug = Helpers::get_setting('debug', '0');
        if ($debug !== '1') { return; }

        $style = 'margin-top:8px;padding-top:6px;border-top:1px solid #e5e7eb;font-size:12px;'
               . 'color:#334155;display:flex;gap:8px;align-items:center;flex-wrap:wrap';

        $stream_url = wp_nonce_url(
            admin_url('admin-ajax.php?action=satori_core_stream_latest_log'),
            'satori-core-admin'
        );

        echo '<div class="satori-core-debug-footer" style="' . esc_attr($style) . '">';
        echo '<span class="tag">SATORI Core v' . esc_html(\Satori\Core\VERSION) . '</span>';
        echo '<span class="sep">|</span>';
        echo '<a class="log-link" href="' . esc_url($stream_url) . '" target="_blank" rel="noopener">' . esc_html__('View latest log', 'satori') . '</a>';
        echo '</div>';
        $printed = true;
    }

    /* ---------- AJAX actions ---------- */
    private function guard_ajax_or_die() : void {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'satori')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'satori-core-admin')) {
            wp_send_json_error(['message' => __('Bad nonce.', 'satori')], 400);
        }
    }

    public function ajax_recheck_updates() : void {
        $this->guard_ajax_or_die();
        wp_update_plugins();
        set_transient('satori_core_last_update_check', current_time('mysql'), HOUR_IN_SECONDS);
        set_transient('satori_core_last_update_source', 'manual', HOUR_IN_SECONDS);
        wp_send_json_success(['message' => __('Update check triggered.', 'satori')]);
    }

    public function ajax_clear_caches() : void {
        $this->guard_ajax_or_die();
        delete_site_transient('update_plugins');
        delete_site_transient('satori_core_update_payload');
        delete_transient('satori_core_last_update_check');
        delete_transient('satori_core_last_update_source');
        wp_send_json_success(['message' => __('Caches cleared.', 'satori')]);
    }

    public function ajax_export_settings() : void {
        $this->guard_ajax_or_die();
        $data = get_option('satori_core_settings', []);
        wp_send_json_success(['settings' => $data]);
    }

    public function ajax_write_test_log() : void {
        $this->guard_ajax_or_die();
        Logger::writeStatic('debug', 'Test log entry via Tools/Settings', [
            'time' => current_time('mysql'),
            'user' => get_current_user_id(),
        ]);
        wp_send_json_success(['message' => __('Test log written.', 'satori')]);
    }

    public function ajax_stream_latest_log() : void {
        if (! current_user_can('manage_options')) {
            wp_die(-1);
        }
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (! wp_verify_nonce($nonce, 'satori-core-admin')) {
            status_header(403);
            wp_die(__('Bad nonce.', 'satori'));
        }
        Logger::streamLatestLogStatic();
    }
}
