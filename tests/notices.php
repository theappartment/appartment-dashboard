<?php
define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);
function self_admin_url($path = '') { return 'https://example.test/wp-admin/' . $path; }
function wp_parse_url($url) { return parse_url($url); }
function esc_url_raw($url) { return filter_var($url, FILTER_SANITIZE_URL); }
function sanitize_textarea_field($text) { return trim(strip_tags($text)); }
function sanitize_text_field($text) { return trim(strip_tags($text)); }
function add_query_arg($key, $value, $url) { return $url . (strpos($url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode($value); }
require __DIR__ . '/../appartment-dashboard/includes/class-notice-center.php';
function check($condition, $label) { if (!$condition) { throw new Exception($label); } echo "PASS $label\n"; }
$now = 2000000000;
$item = array('text' => '<script>unsafe</script> Notice', 'type' => 'error', 'source' => self_admin_url('plugins.php?_wpnonce=secret&page=example'), 'screen' => '<b>Plugins</b>');
$rows = Appartment_Notice_Center::normalize(array($item), array(), $now);
$row = reset($rows);
check(count($rows) === 1 && strpos($row['text'], '<') === false, 'Archive stores text without executable HTML');
check(strpos($row['source'], 'secret') === false && strpos($row['source'], 'page=example') !== false, 'Source preserves routing and removes nonces');
$again = Appartment_Notice_Center::normalize(array($item), $rows, $now + 20);
check(count($again) === 1 && reset($again)['first'] === $now && reset($again)['seen'] === $now + 20, 'Deduplication preserves first and last timestamps');
$bad = $item; $bad['source'] = 'https://evil.test/wp-admin/plugins.php';
check(!Appartment_Notice_Center::normalize(array($bad), array(), $now), 'External source rejected');
$bad['source'] = 'https://user:pass@example.test/wp-admin/plugins.php';
check(!Appartment_Notice_Center::normalize(array($bad), array(), $now), 'Credential-bearing source rejected');
check(!Appartment_Notice_Center::normalize(array(), $rows, $now + 31 * DAY_IN_SECONDS), 'History expires after 30 days');
$many = array();
for ($i = 0; $i < 120; $i++) { $entry = $item; $entry['text'] = 'Notice ' . $i; $many[] = $entry; }
$rows = array();
foreach (array_chunk($many, 40) as $batch) { $rows = Appartment_Notice_Center::normalize($batch, $rows, $now++); }
check(count($rows) === 100, 'History bounded to 100 notices');
check(!Appartment_Notice_Center::normalize(array(null, array('text' => array())), array(), $now), 'Malformed entries ignored');

// Exercise the AJAX boundary with WordPress services simulated.
$allowed = true; $user = 7; $blog = 2; $meta = array();
function current_user_can($cap) { global $allowed; return $allowed; }
function check_ajax_referer($action, $field) { if (($_POST[$field] ?? '') !== 'valid') { throw new RuntimeException('nonce'); } }
function wp_send_json_error($data, $status) { throw new RuntimeException((string) $status); }
function wp_send_json_success($data) { throw new RuntimeException('success'); }
function get_current_blog_id() { global $blog; return $blog; }
function get_current_user_id() { global $user; return $user; }
function get_user_meta($id, $key, $single) { global $meta; return $meta[$id][$key] ?? array(); }
function update_user_meta($id, $key, $value) { global $meta; $meta[$id][$key] = $value; }
function wp_unslash($value) { return $value; }
function admin_url($path = '') { return self_admin_url($path); }
function network_admin_url($path = '') { return self_admin_url('network/' . $path); }
function user_admin_url($path = '') { return self_admin_url('user/' . $path); }
function request_collect() { try { Appartment_Notice_Center::collect(); } catch (RuntimeException $error) { return $error->getMessage(); } }
$_POST = array('nonce' => 'invalid');
check(request_collect() === 'nonce' && !$meta, 'Invalid nonce cannot write archive');
$_POST = array('nonce' => 'valid', 'items' => json_encode(array($item)), 'context' => 'site');
$allowed = false;
check(request_collect() === '403' && !$meta, 'Unauthorized user cannot write archive');
$allowed = true;
check(request_collect() === 'success' && count($meta[7]['_appartment_notices_2_site']) === 1, 'Archive written for current user and site');
$user = 8;
check(get_user_meta($user, '_appartment_notices_2_site', true) === array(), 'Other user cannot see previous archive');
$user = 7; $blog = 3;
check(get_user_meta($user, '_appartment_notices_3_site', true) === array(), 'Other site has separate archive');
$_POST['items'] = 'invalid-json';
check(request_collect() === '400', 'Malformed AJAX payload rejected');
