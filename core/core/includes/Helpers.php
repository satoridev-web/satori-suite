<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Helpers — shared helpers (settings getter, nonce/cap checks, etc.)
 * -------------------------------------------------*/
class Helpers {
    private static $instance = null;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    public static function get_setting(string $key, $default = null) {
        $opts = get_option('satori_core_settings', []);
        return $opts[$key] ?? $default;
    }
}
