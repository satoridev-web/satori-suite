<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * UpdateClient — hooks into WP updates (MVP stub)
 * -------------------------------------------------*/
class UpdateClient {
    private static $instance = null;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update_info']);
        add_filter('plugins_api', [$this, 'plugins_api'], 10, 3);
    }

    public function inject_update_info($transient) {
        // MVP: no remote calls yet; leave hook for future
        return $transient;
    }

    public function plugins_api($result, $action, $args) {
        // MVP: support for "View details" modal later
        return $result;
    }
}
