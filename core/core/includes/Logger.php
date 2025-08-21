<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Logger — file-based logging with redaction
 * Supports both static API and legacy instance() calls.
 * -------------------------------------------------*/
class Logger {

    /** Directory (under uploads) for SATORI logs */
    private const LOG_DIR = 'satori-logs';

    /** Filename prefix for core logs */
    private const FILE_PREFIX = 'satori-core-';

    /** Redacted keys */
    private static $redact_keys = ['license','license_key','token','secret','password','apikey','api_key'];

    /* -------- Singleton shim so existing code using instance() keeps working -------- */
    private static $instance = null;
    private function __construct() {}
    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    /* -------- Instance proxies (call the static methods below) -------- */
    public function write(string $level, string $message, array $context = []) : void {
        self::writeStatic($level, $message, $context);
    }
    public function info(string $message, array $context = []) : void   { self::writeStatic('info',    $message, $context); }
    public function warning(string $message, array $context = []) : void{ self::writeStatic('warning', $message, $context); }
    public function error(string $message, array $context = []) : void  { self::writeStatic('error',   $message, $context); }
    public function debug(string $message, array $context = []) : void  { self::writeStatic('debug',   $message, $context); }
    public function stream_latest_log() : void { self::streamLatestLogStatic(); }
    public function get_latest_log_path() : string { return self::getLatestLogPathStatic(); }

    /* =========================
       Static implementations
       ========================= */

    /**
     * Ensure log directory exists; return full path to today's log file.
     */
    public static function getLatestLogPathStatic() : string {
        $upload = wp_get_upload_dir();
        $dir    = trailingslashit($upload['basedir']) . self::LOG_DIR;

        if ( ! is_dir($dir) ) {
            wp_mkdir_p($dir);
        }

        $file = $dir . '/' . self::FILE_PREFIX . gmdate('Y-m-d') . '.log';
        if ( ! file_exists($file) ) {
            // Seed a header so the link never 404s
            $header = sprintf("[%s] init INFO SATORI log started\n", gmdate('c'));
            @file_put_contents($file, $header, FILE_APPEND);
        }
        return $file;
    }

    /**
     * Write a line to today's log (level: info|warning|error|debug).
     * Debug lines are only written if Debug Mode is ON.
     */
    public static function writeStatic(string $level, string $message, array $context = []) : void {
        $level = strtolower($level);
        if ($level === 'debug' && ! self::debug_enabled()) {
            return;
        }

        $file = self::getLatestLogPathStatic();
        $ctx  = self::redact($context);

        $line = sprintf(
            "[%s] %s %s %s\n",
            gmdate('c'),
            strtoupper($level),
            $message,
            $ctx ? wp_json_encode($ctx) : ''
        );

        @file_put_contents($file, $line, FILE_APPEND);
    }

    /**
     * Stream the latest log to the browser (admins only).
     * Sends text/plain with no-cache headers.
     */
    public static function streamLatestLogStatic() : void {
        if ( ! current_user_can('manage_options') ) {
            wp_die(-1);
        }
        $file = self::getLatestLogPathStatic();

        if ( ! file_exists($file) ) {
            status_header(404);
            nocache_headers();
            header('Content-Type: text/plain; charset=utf-8');
            echo "No log for today.";
            exit;
        }

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }

    /**
     * Helper: Debug Mode flag from settings (safe default false).
     */
    private static function debug_enabled() : bool {
        $opts = get_option('satori_core_settings', []);
        return ! empty($opts['debug']);
    }

    /**
     * Redact sensitive strings in context arrays.
     */
    private static function redact(array $context) : array {
        if (empty($context)) return $context;
        $out = [];
        foreach ($context as $k => $v) {
            $key = strtolower((string)$k);
            if (in_array($key, self::$redact_keys, true)) {
                $out[$k] = '***';
            } else {
                $out[$k] = is_scalar($v) ? $v : wp_json_encode($v);
            }
        }
        return $out;
    }
}
