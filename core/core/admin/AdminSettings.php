<?php
namespace Satori\Core\Admin;

use Satori\Core\Includes\Helpers;
use Satori\Core\Includes\Logger;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Admin Settings — central Tools/Settings UI (tabs)
 * -------------------------------------------------*/
class AdminSettings {
    private static $instance = null;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

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

    public function assets() : void {
        /* -------------------------------------------------
         * Fix: Use fully-qualified Core version constant
         * -------------------------------------------------*/
        $ver = defined('\Satori\Core\VERSION') ? \Satori\Core\VERSION : '0.1.0';

        wp_enqueue_style(
            'satori-core-admin',
            SATORI_CORE_URL . 'core/assets/css/admin.css',
            [],
            $ver
        );

        wp_enqueue_script(
            'satori-core-admin',
            SATORI_CORE_URL . 'core/assets/js/admin.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('satori-core-admin', 'SatoriCoreAdmin', [
            'nonce' => wp_create_nonce('satori-core-admin'),
        ]);
    }

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
            ]
        ]);

        add_settings_section('satori_core_general', __('General', 'satori-core'), '__return_false', 'satori_core');
        add_settings_field('site_id', __('Site ID', 'satori-core'), [$this, 'field_text'], 'satori_core', 'satori_core_general', ['key' => 'site_id']);
        add_settings_field('update_channel', __('Updates Channel', 'satori-core'), [$this, 'field_select'], 'satori_core', 'satori_core_general', [
            'key'     => 'update_channel',
            'options' => ['stable' => 'Stable', 'beta' => 'Beta']
        ]);
        add_settings_field('license_key', __('License Key', 'satori-core'), [$this, 'field_text'], 'satori_core', 'satori_core_general', ['key' => 'license_key']);

        add_settings_section('satori_core_debug', __('Debug & Telemetry', 'satori-core'), '__return_false', 'satori_core');
        add_settings_field('debug', __('Debug Mode', 'satori-core'), [$this, 'field_checkbox'], 'satori_core', 'satori_core_debug', ['key' => 'debug']);
        add_settings_field('telemetry', __('Telemetry (opt-in)', 'satori-core'), [$this, 'field_checkbox'], 'satori_core', 'satori_core_debug', ['key' => 'telemetry']);

        /**
         * Allow modules to add their own tabs/fields.
         */
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

    public function field_text($args) : void {
        $opts = get_option('satori_core_settings', []);
        $key  = esc_attr($args['key']);
        $val  = esc_attr($opts[$key] ?? '');
        echo '<input type="text" class="regular-text" name="satori_core_settings['.$key.']" value="'.$val.'" />';
    }

    public function field_select($args) : void {
        $opts = get_option('satori_core_settings', []);
        $key  = esc_attr($args['key']);
        $val  = esc_attr($opts[$key] ?? '');
        echo '<select name="satori_core_settings['.$key.']">';
        foreach (($args['options'] ?? []) as $k => $label) {
            echo '<option value="'.esc_attr($k).'" '.selected($val, $k, false).'>'.esc_html($label).'</option>';
        }
        echo '</select>';
    }

    public function field_checkbox($args) : void {
        $opts = get_option('satori_core_settings', []);
        $key  = esc_attr($args['key']);
        $val  = !empty($opts[$key]);
        echo '<label><input type="checkbox" name="satori_core_settings['.$key.']" value="1" '.checked($val, true, false).'/> ' . esc_html__('Enabled', 'satori-core') . '</label>';
    }

    public function render_page() : void { ?>
        <div class="wrap satori-core-wrap">
            <h1><?php echo esc_html__('SATORI — Tools/Settings', 'satori-core'); ?></h1>

            <form method="post" action="options.php">
                <?php
                    settings_fields('satori_core');
                    do_settings_sections('satori_core');
                    submit_button();
                ?>
            </form>

            <hr/>
            <h2><?php echo esc_html__('Diagnostics', 'satori-core'); ?></h2>
            <p>
                <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin-ajax.php?action=satori_core_copy_diagnostics'), 'satori_core_diag') ); ?>" class="button">
                    <?php echo esc_html__('Copy Diagnostics to Clipboard', 'satori-core'); ?>
                </a>
            </p>
            <div class="satori-core-footer">
                <?php if (Helpers::get_setting('debug')): ?>
                    <span class="tag"><?php echo esc_html__('Debug ON', 'satori-core'); ?></span>
                <?php endif; ?>
                <span>
                    <?php echo esc_html__('Version', 'satori-core'); ?>:
                    <?php echo esc_html( defined('\Satori\Core\VERSION') ? \Satori\Core\VERSION : '0.1.0' ); ?>
                </span>
            </div>
        </div>
    <?php }
}
