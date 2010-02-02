<?php
global $wpdb;

delete_option("dsidxpress");
$wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE option_name LIKE '_transient_idx_%' OR option_name LIKE '_transient_timeout_idx_%'");
?>