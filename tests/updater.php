<?php
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
function plugin_basename($file) { return 'appartment-dashboard/' . basename($file); }
function add_filter(...$args) {}
$hooks = array(); $can_update = true; $deleted = array();
function current_user_can($cap) { global $can_update; return $can_update; }
function add_action($hook, $callback, $priority = 10) { global $hooks; $hooks[$hook][$priority][] = $callback; }
function do_action($hook) { global $hooks; $callbacks = $hooks[$hook] ?? array(); ksort($callbacks); foreach ($callbacks as $items) { foreach ($items as $callback) { $callback(); } } }

$cache = false; $calls = 0; $status = 200;
function get_site_transient($key) { global $cache; return $cache; }
function set_site_transient($key, $value, $ttl) { global $cache; $cache = $value; }
function delete_site_transient($key) { global $cache, $deleted; $deleted[] = $key; do_action('delete_site_transient_' . $key); if ($key === Appartment_GitHub_Updater::CACHE) { $cache = false; } }
function wp_remote_get($url, $args) { global $calls; ++$calls; return array(); }
function is_wp_error($response) { return false; }
function wp_remote_retrieve_response_code($response) { global $status; return $status; }
function wp_remote_retrieve_body($response) { global $release; return json_encode($release); }
require __DIR__ . '/../appartment-dashboard/includes/class-github-updater.php';
$updater = new Appartment_GitHub_Updater('/plugin/appartment-dashboard.php');
$release = array('tag_name' => 'v1.2.0', 'assets' => array(array('name' => 'appartment-dashboard.zip', 'state' => 'uploaded', 'browser_download_url' => 'https://github.com/theappartment/appartment-dashboard/releases/download/v1.2.0/appartment-dashboard.zip')));
function check($condition, $message) { if (!$condition) { throw new Exception($message); } echo "PASS $message\n"; }
function fetch_update() { global $updater; return $updater->update(false, array(), 'appartment-dashboard/appartment-dashboard.php', array()); }
check($updater->update('untouched', array(), 'other/plugin.php', array()) === 'untouched' && $calls === 0, 'Other plugins unaffected');
$result = fetch_update();
check($result['version'] === '1.2.0' && str_ends_with($result['package'], '/appartment-dashboard.zip'), 'Stable release asset accepted');
fetch_update(); check($calls === 1, 'Successful response cached');
$updater->clear_cache(); $release['prerelease'] = true;
check(fetch_update() === false, 'Prerelease rejected');
$updater->clear_cache(); unset($release['prerelease']); $release['assets'][0]['browser_download_url'] = 'https://example.com/package.zip';
check(fetch_update() === false, 'Foreign package rejected');
$updater->clear_cache(); $release['assets'] = array();
check(fetch_update() === false, 'Missing package rejected');
$updater->clear_cache(); $release['tag_name'] = 'invalid';
check(fetch_update() === false, 'Malformed version rejected');
$updater->clear_cache(); $status = 403;
check(fetch_update() === false, 'API failure handled');
$before = $calls; fetch_update(); check($before === $calls, 'API failure cached');

// Reproduce a previous release cached while a newer release is available.
$status = 200;
$release = array('tag_name' => 'v1.1.1', 'assets' => array(array('name' => 'appartment-dashboard.zip', 'state' => 'uploaded', 'browser_download_url' => 'https://github.com/theappartment/appartment-dashboard/releases/download/v1.1.1/appartment-dashboard.zip')));
$stale = array('version' => '1.1.0', 'package' => 'old-package');
$cache = $stale;
check(fetch_update()['version'] === '1.1.0', 'Reproduced stale GitHub cache');
add_action('load-update-core.php', function () { $GLOBALS['observed'] = fetch_update(); }, 10);
$_GET = array(); $before = $calls;
do_action('load-update-core.php');
check($cache === $stale && $calls === $before, 'Ordinary page load preserves cache');
$_GET['force-check'] = '1'; $can_update = false;
do_action('load-update-core.php');
check($cache === $stale && $calls === $before, 'Unauthorized request preserves cache');
$can_update = true;
do_action('load-update-core.php');
check($observed['version'] === '1.1.1' && $calls === $before + 1, 'Manual check fetches latest before core check');
check(in_array('update_plugins', $deleted, true), 'WordPress plugin cache also invalidated');
unset($_GET['force-check']);
if (isset($argv[1])) {
    $release = json_decode(file_get_contents($argv[1]), true);
    $updater->clear_cache();
    $result = fetch_update();
    check($result && $result['version'] === ltrim($release['tag_name'], 'v'), 'Actual public GitHub release payload accepted');
}
