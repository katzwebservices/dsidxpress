<?php

$flushCacheTask = "cron-dsidxpress-flush-cache";

add_action($flushCacheTask, array("dsIDXpress_Cron", "FlushCache"));

if (!wp_next_scheduled($flushCacheTask))
	wp_schedule_event(time(), "daily", $flushCacheTask);

class dsIDXpress_Cron {
	static function FlushCache() {
		global $wpdb;
		$wpdb->query("
			SELECT *
			FROM wp_options
			WHERE
			  option_name LIKE '_transient_%idx_%'
			  AND RIGHT(option_name, 40) IN (
			    SELECT RIGHT(option_name, 40) AS HashedKey
			    FROM wp_options
			    WHERE
			      option_name LIKE '_transient_timeout_idx_%'
			      AND option_value < UNIX_TIMESTAMP()
			  )
		");
	}
}