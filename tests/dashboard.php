<?php
// Minimal WordPress harness: validates filtering without calling external APIs.
define('ABSPATH', __DIR__);
$scenario = $argv[1] ?? 'plain';
$caps = array('read');
if (in_array($scenario, array('sitekit', 'both', 'sitekit-unconfigured'), true)) {
    define('GOOGLESITEKIT_VERSION', 'test');
    $caps[] = 'googlesitekit_view_wp_dashboard_widget';
    if ($scenario !== 'sitekit-unconfigured') { $caps[] = 'googlesitekit_view_dashboard'; }
}
if (in_array($scenario, array('woocommerce', 'both', 'denied'), true)) {
    class WooCommerce {}
    if ($scenario !== 'denied') { $caps[] = 'view_woocommerce_reports'; }
}
function plugin_basename($file) { return 'appartment-dashboard/' . basename($file); }
function add_action(...$args) {}
function add_filter(...$args) {}
function remove_action(...$args) {}
function get_current_screen() { global $scenario; return (object) array('id' => $scenario === 'network' ? 'dashboard-network' : 'dashboard'); }
function is_network_admin() { global $scenario; return $scenario === 'network'; }
function current_user_can($cap) { global $caps; return in_array($cap, $caps, true); }
function wp_add_dashboard_widget($id, $title, $callback) { global $wp_meta_boxes; $wp_meta_boxes['dashboard']['normal']['core'][$id] = compact('id', 'title', 'callback'); }
require __DIR__ . '/../appartment-dashboard/appartment-dashboard.php';
$wp_meta_boxes = array('dashboard' => array('normal' => array('core' => array_fill_keys(array('dashboard_activity', 'google_dashboard_widget', 'woocommerce_dashboard_status', 'woocommerce_dashboard_recent_reviews'), array('id' => 'fixture'))), 'side' => array('high' => array('third_party' => array('id' => 'third_party')))));
$original = $wp_meta_boxes;
Appartment_Dashboard::setup();
Appartment_Dashboard::clean();
$actual = array();
foreach ($wp_meta_boxes['dashboard'] as $priorities) { foreach ($priorities as $boxes) { $actual = array_merge($actual, array_keys($boxes)); } }
$expected = array(Appartment_Dashboard::WIDGET);
if (in_array($scenario, array('sitekit', 'both'), true)) { $expected[] = 'google_dashboard_widget'; }
if (in_array($scenario, array('woocommerce', 'both'), true)) { $expected[] = 'woocommerce_dashboard_status'; }
if ($scenario === 'network') {
    if ($wp_meta_boxes !== $original) { throw new Exception('Network dashboard modified'); }
} else {
    sort($actual); sort($expected);
    if ($actual !== $expected) { throw new Exception('Unexpected widgets: ' . json_encode($actual)); }
    $hidden = Appartment_Dashboard::visible_widgets(array_merge($expected, array('other')), get_current_screen());
    if ($hidden !== array('other')) { throw new Exception('Allowed widgets hidden'); }
}
echo "PASS $scenario\n";
