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

class UpdateClient {
    private static $instance = null;

    private $plugin_basename;
    private $plugin_slug = 'satori-core';
    private $default_live_base = 'https://updates.wordpressed.com.au/plugins/';

    public static function instance() : self {
        return self::$instance ?? (self::$instance = new self());
    }

    private function __construct() {
        $this->plugin_basename = plugin_basename(\Satori\Core\PLUGIN_FILE);

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
        // Record update-check metadata for Diagnostics
        set_transient('satori_core_last_update_check', current_time('mysql'), HOUR_IN_SECONDS);
        set_transient('satori_core_last_update_source', isset($src) ? $src : 'unknown', HOUR_IN_SECONDS);

        if (!$payload) {
            set_transient('satori_core_last_update_check', current_time('mysql'), HOUR_IN_SECONDS);
            set_transient('satori_core_last_update_source', isset($src) ? $src : 'none', HOUR_IN_SECONDS);
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
                'tested'       => $payload['tested']         ?? '',
                'requires'     => $payload['requires']       ?? '6.0',
                'requires_php' => $payload['requires_php']   ?? '8.0',
                'package'      => $this->resolve_package($payload),
                'url'          => $payload['homepage']       ?? 'https://satori.com.au/',
            ];
            $transient->response[$this->plugin_basename] = $obj;
            Logger::writeStatic('info', 'Update available', ['current' => $current, 'remote' => $remote, 'source' => $src ?? 'unknown']);
        } else {
            unset($transient->response[$this->plugin_basename]);
            Logger::writeStatic('info', 'Up to date', ['current' => $current, 'remote' => $remote, 'source' => $src ?? 'unknown']);
        }

        return $transient;
    }

    /* -------------------------------------------------
     * Plugins API (details modal)
     * -------------------------------------------------*/
    public function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information') { return $result; }
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) { return $result; }

        $payload = $this->get_payload($src);
        if (!$payload) { return $result; }

        $info = new \stdClass();
        $info->name          = $payload['name']           ?? 'SATORI Core';
        $info->slug          = $this->plugin_slug;
        $info->version       = $this->resolve_version($payload);
        $info->author        = $payload['author']         ?? 'Satori Graphics';
        $info->author_profile= $payload['author_profile'] ?? 'https://satori.com.au/';
        $info->homepage      = $payload['homepage']       ?? 'https://satori.com.au/';
        $info->requires      = $payload['requires']       ?? '6.0';
        $info->requires_php  = $payload['requires_php']   ?? '8.0';
        $info->tested        = $payload['tested']         ?? '';
        $info->download_link = $this->resolve_package($payload);
        $info->sections      = (array)($payload['sections'] ?? []);

        // Tiny debug panel in modal
        $info->sections['satori_debug'] =
            '<p style="opacity:.7"><code>SATORI UpdateClient</code> source: '
            . esc_html($src ?? 'unknown') . '</p>';

        return $info;
    }

    /* -------------------------------------------------
     * Payload resolution (mock/live + caching)
     * -------------------------------------------------*/
    private function get_payload(?string &$source = null) : ?array {
        $cached = get_site_transient('satori_core_update_payload');
        if (is_array($cached)) {
            $source = 'cache';
            Logger::writeStatic('debug', 'Using cached update payload');
            return $cached;
        }

        // Mock path via constant, if provided
        if (defined('SATORI_CORE_UPDATE_MOCK')) {
            $mock = $this->resolve_mock_path(SATORI_CORE_UPDATE_MOCK);
            $payload = $this->read_json_file($mock);
            if (is_array($payload)) {
                set_site_transient('satori_core_update_payload', $payload, 5 * MINUTE_IN_SECONDS);
                $source = 'mock-json';
                Logger::writeStatic('info', 'Loaded mock update payload', ['path' => $mock]);
                return $payload;
            }
        }

        // Live
        $endpoint = $this->build_live_endpoint();
        $payload  = $this->http_json($endpoint);
        if (is_array($payload)) {
            set_site_transient('satori_core_update_payload', $payload, 5 * MINUTE_IN_SECONDS);
            $source = 'remote';
            Logger::writeStatic('info', 'Loaded live update payload', ['endpoint' => $endpoint]);
            return $payload;
        }

        $source = 'none';
        Logger::writeStatic('warning', 'No update payload available');
        return null;
    }

    private function resolve_version(array $payload) : ?string {
        return $payload['new_version'] ?? $payload['version'] ?? null;
    }

    private function resolve_package(array $payload) : string {
        return $payload['package'] ?? $payload['download_url'] ?? '';
    }

    private function resolve_mock_path(string $path) : string {
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
                    $base = trailingslashit(rtrim($data['UpdateURI'], '/')) . 'plugins/';
                }
            }
        }
        return $base . $this->plugin_slug;
    }

    private function read_json_file(string $path) : ?array {
        if (!file_exists($path)) { return null; }
        $raw = file_get_contents($path);
        if ($raw === false) { return null; }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function http_json(string $url) : ?array {
        $res = wp_remote_get($url, ['timeout' => 8]);
        if (is_wp_error($res)) { return null; }
        $code = wp_remote_retrieve_response_code($res);
        if ($code !== 200) { return null; }
        $raw = wp_remote_retrieve_body($res);
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
}
