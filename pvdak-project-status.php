<?php
/**
 * PVDak public, read-only release status (standalone; no WordPress bootstrap).
 *
 * Deliberately exposes only release IDs, versions and boolean health signals.
 * Never output paths, secrets, users, database settings or private log contents.
 * This endpoint is not a substitute for a WordPress/browser integration test.
 */
declare(strict_types=1);

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', array('GET', 'HEAD'), true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');

$wp_root = dirname(__DIR__, 3);
$home = dirname($wp_root);
$state = $home . '/.pvdak-deploy';
$read_sha = static function (string $path): ?string {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $value = trim((string) @file_get_contents($path, false, null, 0, 128));
    return preg_match('/\A[0-9a-f]{40}\z/D', $value) === 1 ? $value : null;
};
$read_version = static function (string $path): ?string {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $header = (string) @file_get_contents($path, false, null, 0, 8192);
    return preg_match('/^[ \t]*\*?[ \t]*Version:[ \t]*([0-9]+(?:\.[0-9]+)*)/mi', $header, $match)
        ? $match[1] : null;
};
$mtime_utc = static function (string $path): ?string {
    if (!is_file($path)) {
        return null;
    }
    $mtime = @filemtime($path);
    return is_int($mtime) ? gmdate('Y-m-d\TH:i:s\Z', $mtime) : null;
};
/** Compare versioned Core source trees without trusting an executable script. */
$manifest = static function (string $root): ?array {
    if (!is_dir($root) || is_link($root)) {
        return null;
    }
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        $items = array();
        foreach ($files as $file) {
            if ($file->isLink() || !$file->isFile()) {
                return null;
            }
            $name = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $hash = @hash_file('sha256', $file->getPathname());
            if (!is_string($hash)) {
                return null;
            }
            $items[$name] = $hash;
        }
        ksort($items);
        return $items ?: null;
    } catch (Throwable $exception) {
        return null;
    }
};

$deployed = $read_sha($state . '/deployed');
$failed = $read_sha($state . '/failed');
$fetched = $read_sha($home . '/repositories/pvdakcom/.git/refs/remotes/pvdak-deploy/main');
if ($fetched === null) {
    $packed = $home . '/repositories/pvdakcom/.git/packed-refs';
    if (is_file($packed) && is_readable($packed)) {
        $packed_content = (string) @file_get_contents($packed, false, null, 0, 1048576);
        if (preg_match('/^([0-9a-f]{40}) refs\/remotes\/pvdak-deploy\/main$/m', $packed_content, $match)) {
            $fetched = $match[1];
        }
    }
}
$bundled_core = __DIR__ . '/packages/pvdak-core';
$installed_core = $wp_root . '/wp-content/plugins/pvdak-core';
$bundle_manifest = $manifest($bundled_core);
$installed_manifest = $manifest($installed_core);
$release_current = $deployed !== null && $fetched !== null ? $deployed === $fetched : null;

// The existing 15-minute deploy job already runs the read-only source suite
// before accepting a new release. Read only a bounded tail of its private log:
// expose the pass signal, never the log text, paths or case-related data.
$preflight_confirmed = null;
$deploy_log = $state . '/deploy.log';
if ($deployed !== null && is_file($deploy_log) && is_readable($deploy_log)) {
    $handle = @fopen($deploy_log, 'rb');
    if ($handle) {
        $size = @filesize($deploy_log);
        if (is_int($size) && $size > 0) {
            @fseek($handle, max(0, $size - 262144), SEEK_SET);
            $tail = (string) @stream_get_contents($handle, 262144);
            $preflight_confirmed = preg_match(
                '/^\\[[0-9TZ:-]+\\] SOURCE_PREFLIGHT_OK ' . preg_quote($deployed, '/') . '$/m',
                $tail
            ) === 1 ? true : null;
        }
        fclose($handle);
    }
}

$data = array(
    'project' => 'PVDak',
    'schema_version' => 1,
    'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
    'deployment' => array(
        'deployed_sha' => $deployed,
        'host_fetched_main_sha' => $fetched,
        'deployed_matches_host_fetched_main' => $release_current,
        'failed_release_sha' => $failed,
        'last_deploy_at_utc' => $mtime_utc($state . '/deployed'),
        'last_successful_cron_poll_at_utc' => $mtime_utc($state . '/last-check'),
    ),
    'versions' => array(
        'theme' => $read_version(__DIR__ . '/style.css'),
        'core_bundled' => $read_version($bundled_core . '/pvdak-core.php'),
        'core_installed' => $read_version($installed_core . '/pvdak-core.php'),
    ),
    'checks' => array(
        'core_installed' => is_file($installed_core . '/pvdak-core.php'),
        'source_preflight_confirmed_in_recent_deploy_log' => $preflight_confirmed,
        'core_file_tree_matches_bundled' => $bundle_manifest !== null &&
            $installed_manifest !== null && $bundle_manifest === $installed_manifest,
        'rtl_stylesheet_present' => is_file(__DIR__ . '/rtl.css'),
        'swiper_js_present' => is_file(__DIR__ . '/assets/vendor/swiper-14.3.0/swiper-bundle.min.js'),
        'swiper_css_present' => is_file(__DIR__ . '/assets/vendor/swiper-14.3.0/swiper-bundle.min.css'),
    ),
    'limitations' => array(
        'wordpress_active_plugins_not_checked',
        'latest_github_main_must_be_compared_externally',
        'source_and_browser_regressions_not_executed_by_this_endpoint',
    ),
);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
