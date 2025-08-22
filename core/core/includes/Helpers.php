<?php
namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

/* -------------------------------------------------
 * Helpers — shared helpers
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

    /* -------------------------------------------------
     * Log helpers
     * -------------------------------------------------*/

    /**
     * Returns candidate (dir, url) pairs where logs may live.
     * We search several common subpaths to stay compatible with different setups.
     *
     * Priority order:
     *  1) Constant-defined path/url (if available)
     *  2) wp-content/uploads/satori/logs
     *  3) wp-content/uploads/satori-core/logs
     *  4) wp-content/uploads/satori_core/logs
     *  5) wp-content/uploads/logs (generic)
     */
    private static function get_log_candidates() : array {
        $candidates = [];

        // If constants are provided anywhere in the stack, prefer them
        if (defined('SATORI_CORE_LOG_DIR') && defined('SATORI_CORE_LOG_URL')) {
            $candidates[] = [ (string)SATORI_CORE_LOG_DIR, (string)SATORI_CORE_LOG_URL ];
        }

        $uploads = wp_upload_dir();
        $basedir = trailingslashit($uploads['basedir']);
        $baseurl = trailingslashit($uploads['baseurl']);

        $subs = [
            'satori/logs',
            'satori-core/logs',
            'satori_core/logs',
            'logs',
        ];

        foreach ($subs as $sub) {
            $dir = wp_normalize_path($basedir . $sub);
            $url = $baseurl . $sub;
            $candidates[] = [ $dir, $url ];
        }

        // De-dup just in case
        $uniq = [];
        $out  = [];
        foreach ($candidates as $pair) {
            $key = $pair[0] . '|' . $pair[1];
            if (!isset($uniq[$key])) {
                $uniq[$key] = true;
                $out[] = $pair;
            }
        }
        return $out;
    }

    /**
     * Finds the newest log file and returns [abs_path, public_url] or [null, null] if none.
     */
    private static function find_latest_log() : array {
        $latestPath = null;
        $latestUrl  = null;
        $latestTime = 0;

        foreach (self::get_log_candidates() as list($dir, $url)) {
            if (!is_dir($dir)) {
                continue;
            }

            // Prefer satori-*.log but accept any .log as fallback
            $files = glob($dir . '/satori-*.log');
            if (!$files) {
                $files = glob($dir . '/*.log');
            }
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                $mtime = @filemtime($file);
                if ($mtime && $mtime > $latestTime) {
                    $latestTime = $mtime;
                    $latestPath = $file;
                    $latestUrl  = trailingslashit($url) . basename($file);
                }
            }
        }

        return [$latestPath, $latestUrl];
    }

    /**
     * Public helper used by the footer / AJAX to link to the newest log.
     */
    public static function get_latest_log_url() : ?string {
        list($path, $url) = self::find_latest_log();
        return $url ?: null;
    }
}
