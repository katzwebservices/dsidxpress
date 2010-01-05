<?php
/*
Plugin Name: dsIDXpress
Plugin URI: http://wordpress.org/extend/plugins/dssearchagent-wordpress-edition/
Description: This plugin allows WordPress to embed live real estate data directly into the blog. You MUST have Diverse Solutions account to use this plugin.
Author: Diverse Solutions
Author URI: http://www.diversesolutions.com/
Version: 1.0-beta9
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
require_once(ABSPATH . "wp-admin/includes/plugin.php");

register_activation_hook(__FILE__, "dsSearchAgent::FlushRewriteRules");

if(!defined('PHP_VERSION_ID'))
{
	$version = explode('.',PHP_VERSION);
	define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

$dsSearchAgent_PluginName = str_replace(".php", "", basename(__FILE__));
$dsSearchAgent_PluginUrl = WP_PLUGIN_URL . "/" . $dsSearchAgent_PluginName . "/";
$dsSearchAgent_PluginPath = str_replace("\\", "/", WP_PLUGIN_DIR . "/" . $dsSearchAgent_PluginName . "/");
$dsSearchAgent_PluginData = get_plugin_data(__FILE__);
$dsSearchAgent_PluginVersion = $dsSearchAgent_PluginData["Version"];
$dsSearchAgent_Options = get_option("dssearchagent-wordpress-edition");

if ($dsSearchAgent_Options["Activated"]) {
	require_once("widget-search.php");
	require_once("widget-list-areas.php");
	require_once("widget-listings.php");
}
require_once("rewrite.php");
require_once("api-request.php");

if (is_admin()) {
	require_once($dsSearchAgent_PluginPath . "admin.php");
} else {
	require_once("client.php");
	require_once("shortcodes.php");
}

add_action("widgets_init", "dsSearchAgent::InitWidgets");
class dsSearchAgent {
	static function InitWidgets() {
		global $dsSearchAgent_Options;
		if ($dsSearchAgent_Options["Activated"]) {
			register_widget("dsSearchAgent_SearchWidget");
			register_widget("dsSearchAgent_ListAreasWidget");
			register_widget("dsSearchAgent_ListingsWidget");
		}
	}
	static function FlushRewriteRules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
}
?>