<?php
/*
Plugin Name: dsIDXpress
Plugin URI: http://www.dsidxpress.com/
Description: This plugin allows WordPress to embed live real estate data from an MLS directly into a blog. You MUST have a dsIDXpress account to use this plugin.
Author: Diverse Solutions
Author URI: http://www.diversesolutions.com/
Version: 1.1.3
*/

/*
	Copyright 2009, Diverse Solutions

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

	http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.
*/
global $wp_version;

require_once(ABSPATH . "wp-admin/includes/plugin.php");
$pluginData = get_plugin_data(__FILE__);

define("DSIDXPRESS_OPTION_NAME", "dsidxpress");
define("DSIDXPRESS_API_OPTIONS_NAME", "dsidxpress-api-options");

define("DSIDXPRESS_MIN_VERSION_PHP", "5.2.0");
define("DSIDXPRESS_MIN_VERSION_WORDPRESS", "2.8");
define("DSIDXPRESS_PLUGIN_URL", WP_PLUGIN_URL . "/dsidxpress/");
define("DSIDXPRESS_PLUGIN_VERSION", $pluginData["Version"]);

if (version_compare(phpversion(), DSIDXPRESS_MIN_VERSION_PHP) == -1 || version_compare($wp_version, DSIDXPRESS_MIN_VERSION_WORDPRESS) == -1) {
	add_action("admin_notices", "dsidxpress_DisplayVersionWarnings");
	return;
}

if (get_option("dssearchagent-wordpress-edition")) {
	$mergedOption = get_option("dssearchagent-wordpress-edition");
	if (is_array(get_option("dsidxpress-custom-options")))
		$mergedOption = array_merge($mergedOption, get_option("dsidxpress-custom-options"));
	update_option(DSIDXPRESS_OPTION_NAME, $mergedOption);
	delete_option("dssearchagent-wordpress-edition");
	delete_option("dsidxpress-custom-options");
}

require_once("widget-search.php");
require_once("widget-list-areas.php");
require_once("widget-listings.php");
require_once("rewrite.php");
require_once("api-request.php");
require_once("cron.php");
require_once("xml-sitemaps.php");

if (is_admin()) {
	// this is needed specifically for development as PHP seems to choke when 1) loading this in admin, 2) using windows, 3) using directory junctions
	include_once(str_replace("\\", "/", WP_PLUGIN_DIR) . "/dsidxpress/admin.php");
} else {
	require_once("client.php");
	require_once("shortcodes.php");
}

register_activation_hook(__FILE__, "dsidxpress_FlushRewriteRules");
add_action("widgets_init", "dsidxpress_InitWidgets");

// not in a static class to prevent PHP < 5 from failing when including and interpreting this particular file
function dsidxpress_DisplayVersionWarnings() {
	global $wp_version;

	$currentVersionPhp = phpversion();
	$currentVersionWordPress = $wp_version;

	$minVersionPhp = DSIDXPRESS_MIN_VERSION_PHP;
	$minVersionWordPress = DSIDXPRESS_MIN_VERSION_WORDPRESS;

	echo <<<HTML
		<div class="error">
			In order to use the dsIDXpress plugin, your web server needs to be running at least PHP {$minVersionPhp} and WordPress {$minVersionWordPress}.
			You're currently using PHP {$currentVersionPhp} and WordPress {$currentVersionWordPress}. Please consider upgrading.
		</div>
HTML;
}
function dsidxpress_InitWidgets() {
	register_widget("dsSearchAgent_SearchWidget");
	register_widget("dsSearchAgent_ListAreasWidget");
	register_widget("dsSearchAgent_ListingsWidget");
}
function dsidxpress_FlushRewriteRules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

?>