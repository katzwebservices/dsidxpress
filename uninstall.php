<?php
global $wpdb;

delete_option("dsidxpress");
$wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE option_name LIKE '_transient_idx_%' OR option_name LIKE '_transient_timeout_idx_%'");

$flushCacheTask = "cron-dsidxpress-flush-cache";
function dsidxpressRemoveCacheFlush() {
	wp_clear_scheduled_hook($flushCacheTask);
}
delete_action($flushCacheTask, "dsidxpressRemoveCacheFlush");