<?php
/**
 * SATORI Core — Update Client (singleton, cached, logged)
 *
 * - Uses SATORI_CORE_UPDATE_MOCK if defined (absolute or plugin-relative path)
 * - Otherwise, hits live endpoint built from UpdateURI (or default base)
 * - Caches payload in site transient: satori_core_update_payload
 * - Logs source + results to SATORI Logger
 *
 * @package Satori\Core\Includes
 */

namespace Satori\Core\Includes;

defined('ABSPATH') || exit;

use Satori\Core\Includes\Logger;
use Satori\Core\Includes\Helpers;

class UpdateClient {
    /** @var self|null */
    private static $instance = null;

    /** @var string e.g. "core/satori-core.php" */
    private $plugin_basename;

    /** @var string e.g. "core" */
    private $plugin_slug;

    /** Cache key + TTL */
    private const CACHE_KEY = 'satori_core_update_payload';
    private const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

    /** Default live base if UpdateURI missing */
    private $default_live_base = 'https://updates.wordpressed.com.au';

    /** Singleton */
    public static function instance(): self {
        return self::$instance ?? (self::$instance = new self());
    }

    /** Private constructor */
    private function __construct() {
        $this->plugin_basename = plugin_basename(\Satori\Core\PLUGIN_FILE); // e.g. "core/satori-core.php"
        $this->plugin_slug     = dirname($this->plugin_basename);           // e.g. "core"

        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update_info']);
        add_filter('plugins_api',                           [$this, 'plugins_api'], 10, 3);
    }

    /** For parity with caller style */
    public function boot(): void {
        // hooks already set in ctor
    }

    /* -------------------------------------------------
     * Update Transient Injection
     * -------------------------------------------------*/
    public function inject_update_info($transient) {
        if (!is_object($transient)) { $transient = new \stdClass(); }
        if (!isset($transient->response)) { $transient->response = []; }

        $payload = $this->get_payload($src);
        if (!$payload) {
            unset($transient->response[$this->plugin_basename]);
            Logger::writeStatic('info', 'Update check: no payload', ['source' => $src ?? 'none']);
            return $transient;
        }

        $current = defined('\Satori\Core\VERSION') ? (string)\Satori\Core\VERSION : '0.0.0';
        $remote  = $this->resolve_version($payload); // supports version|new_version

        if (!$remote) {
            unset($transient->response[$this->plugin_basename]);
            Logger::writeStatic('warning', 'Payload missing version fields', ['source' => $src ?? 'unknown']);
            return $transient;
        }

        if (version_compare($current, $remote, '<')) {
            $obj = (object)[
                'slug'         => $this->plugin_slug,
                'plugin'       => $this->plugin_basename,
                'new_version'  => $remote,
                'package'      => $this->resolve_package($payload), // download_url|package
                'tested'       => $payload['tested']       ?? '',
                'requires'     => $payload['requires']     ?? '',
                'requires_php' => $payload['requires_php'] ?? '',
                'url'          => $payload['homepage']     ?? 'https://satori.com.au/',
                'icons'        => [],
                'banners'      => [],
            ];
            $transient->response[$this->plugin_basename] = $obj;
            Logger::writeStatic('info', 'Update available', ['source' => $src ?? 'unknown', 'remote' => $remote, 'current' => $current]);
        } else {
            unset($transient->response[$this->plugin_basename]);
            Logger::writeStatic('info', 'No update available', ['source' => $src ?? 'unknown', 'remote' => $remote, 'current' => $current]);
        }

        return $transient;
    }

    /* -------------------------------------------------
     * Plugin Info Modal
     * -------------------------------------------------*/
    public function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information') { return $result; }
        if (empty($args->slug) || $args->slug !== $this->plugin_slug) { return $result; }

        $payload = $this->get_payload($src);
        if (!$payload) { return $result; }

        $version = $this->resolve_version($payload) ?? '';
        $info = new \stdClass();
        $info->name           = $payload['name']           ?? 'SATORI Core';
        $info->slug           = $this->plugin_slug;
        $info->version        = $version;
        $info->author         = $payload['author']         ?? 'Satori Graphics';
        $info->author_profile = $payload['author_profile'] ?? 'https://satori.com.au/';
        $info->homepage       = $payload['homepage']       ?? 'https://satori.com.au/';
        $info->requires       = $payload['requires']       ?? '6.0';
        $info->requires_php   = $payload['requires_php']   ?? '8.0';
        $info->tested         = $payload['tested']         ?? '';
        $info->download_link  = $this->resolve_package($payload);
        $info->sections       = (array)($payload['sections'] ?? []);

        // Tiny debug panel in modal
        $info->sections['satori_debug'] =
            '<p style="opacity:.7"><code>SATORI UpdateClient</code> source: '
            . esc_html($src ?? 'unknown') . '</p>';

        return $info;
    }

    /* -------------------------------------------------
     * Payload resolution (cache → mock → remote)
     * -------------------------------------------------*/
    private function get_payload(?string &$src = null) : ?array {
        // 0) Cache
        $cached = get_site_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            $src = 'cache';
            return $cached;
        }

        // 1) Mock JSON?
        if (defined('SATORI_CORE_UPDATE_MOCK') && SATORI_CORE_UPDATE_MOCK) {
            $path = $this->resolve_mock_path((string) SATORI_CORE_UPDATE_MOCK);
            $data = $this->read_json_file($path);
            if (is_array($data)) {
                $src = 'mock-json';
                set_site_transient(self::CACHE_KEY, $data, self::CACHE_TTL);
                Logger::writeStatic('info', 'Fetched update payload (mock-json)', ['path' => $path, 'version_field' => $this->inspect_version_field($data)]);
                return $data;
            }
            Logger::writeStatic('warning', 'Mock JSON not readable or invalid', ['path' => $path]);
            // fall through to remote
        }

        // 2) Remote
        $endpoint = $this->build_live_endpoint();
        $data = $this->http_json($endpoint);
        if (is_array($data)) {
            $src = 'remote';
            set_site_transient(self::CACHE_KEY, $data, self::CACHE_TTL);
            Logger::writeStatic('info', 'Fetched update payload (remote)', ['endpoint' => $endpoint, 'version_field' => $this->inspect_version_field($data)]);
            return $data;
        }

        Logger::writeStatic('warning', 'Remote update payload missing/invalid', ['endpoint' => $endpoint]);
        set_site_transient(self::CACHE_KEY, null, 2 * MINUTE_IN_SECONDS);
        return null;
    }

    private function resolve_mock_path(string $path): string {
        $path = wp_normalize_path($path);
        if ($this->is_absolute($path)) {
            return $path;
        }
        $plugin_dir = plugin_dir_path(\Satori\Core\PLUGIN_FILE);
        return wp_normalize_path($plugin_dir . ltrim($path, '/'));
    }

    private function build_live_endpoint(): string {
        // Prefer UpdateURI from plugin header if present
        $base = $this->default_live_base;
        if (function_exists('get_plugin_data')) {
            $file = \Satori\Core\PLUGIN_FILE;
            if (file_exists($file)) {
                $data = get_plugin_data($file, false, false);
                if (!empty($data['UpdateURI'])) {
                    $base = trim((string)$data['UpdateURI']);
                }
            }
        }

        // Expected path: <base>/plugins/<slug>.json
        $url = trailingslashit(untrailingslashit($base)) . 'plugins/' . rawurlencode($this->plugin_slug) . '.json';

        // Pass optional params
        $current = defined('\Satori\Core\VERSION') ? (string)\Satori\Core\VERSION : '0.0.0';
        $channel = Helpers::get_setting('update_channel', 'stable');
        $site_id = Helpers::get_setting('site_id', '');
        $license = Helpers::get_setting('license_key', '');

        return add_query_arg([
            'channel' => in_array($channel, ['stable','beta'], true) ? $channel : 'stable',
            'site_id' => $site_id,
            'license' => $license,
            'ver'     => $current,
        ], $url);
    }

    private function http_json(string $url) : ?array {
        $resp = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);
        if (is_wp_error($resp)) {
            Logger::writeStatic('warning', 'HTTP error fetching update JSON', ['error' => $resp->get_error_message()]);
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        if ($code !== 200 || empty($body)) {
            Logger::writeStatic('warning', 'Bad response from update endpoint', ['code' => $code]);
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private function read_json_file(string $path) : ?array {
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) { return null; }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function is_absolute(string $path): bool {
        if (DIRECTORY_SEPARATOR === '/') {
            return (bool)preg_match('#^/|^~/#', $path);
        }
        // Windows
        return (bool)preg_match('#^[A-Za-z]:\\\\|^\\\\\\\\#', $path);
    }

    /* ---------- helpers for schema variations ---------- */

    private function resolve_version(array $payload) : ?string {
        if (!empty($payload['version']))     return (string)$payload['version'];
        if (!empty($payload['new_version'])) return (string)$payload['new_version'];
        return null;
    }

    private function resolve_package(array $payload) : string {
        if (!empty($payload['download_url'])) return (string)$payload['download_url'];
        if (!empty($payload['package']))      return (string)$payload['package'];
        return '';
    }

    private function inspect_version_field(array $payload) : string {
        if (isset($payload['version']))     return 'version';
        if (isset($payload['new_version'])) return 'new_version';
        return 'none';
    }
}
