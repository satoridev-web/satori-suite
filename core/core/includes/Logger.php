<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Logger — lightweight daily file logger (masks secrets)
 * -------------------------------------------------*/
class Logger {
    private static $instance = null;
    private $dir;

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        $upload_dir = wp_get_upload_dir();
        $this->dir = trailingslashit($upload_dir['basedir']) . 'satori-logs/';
        if (!file_exists($this->dir)) {
            wp_mkdir_p($this->dir);
        }
    }

    public function log(string $message, array $context = []) : void {
        if (!apply_filters('satori/core/logger_enabled', true)) { return; }

        $mask = static function($val) {
            if (!is_string($val)) { return $val; }
            if (preg_match('/(sk_live|pk_live|license|key|secret)/i', $val)) {
                return substr($val, 0, 4) . '••••' . substr($val, -2);
            }
            return $val;
        };
        $context = array_map($mask, $context);

        $line = sprintf(
            "[%s] %s %s\n",
            gmdate('c'),
            $message,
            $context ? wp_json_encode($context) : ''
        );

        $file = $this->dir . 'satori-core-' . gmdate('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND);
    }
}
