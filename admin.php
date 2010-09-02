<?php

add_action("admin_init", array("dsSearchAgent_Admin", "Initialize"));
add_action("admin_menu", array("dsSearchAgent_Admin", "AddMenu"));
add_action("admin_notices", array("dsSearchAgent_Admin", "DisplayAdminNotices"));
add_action("wp_ajax_dsidxpress-dismiss-notification", array("dsSearchAgent_Admin", "DismissNotification"));

define('SCRIPT_DEBUG', true);
wp_enqueue_script('jquery');
add_thickbox();
wp_enqueue_script('dsidxpress_admin_options', DSIDXPRESS_PLUGIN_URL . 'js/admin-options.js', array(), DSIDXPRESS_PLUGIN_VERSION);

class dsSearchAgent_Admin {
	static $HeaderLoaded = null;
	static function AddMenu() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		add_menu_page('dsIDXpress', 'dsIDXpress', "manage_options", "dsidxpress", "", DSIDXPRESS_PLUGIN_URL . 'assets/idxpress_LOGOicon.png');

		$activationPage = add_submenu_page("dsidxpress", "dsIDXpress Activation", "Activation", "manage_options", "dsidxpress", array("dsSearchAgent_Admin", "Activation"));
		add_action("admin_print_scripts-{$activationPage}", array("dsSearchAgent_Admin", "LoadHeader"));

		if (isset($options["Activated"])) {
			$optionsPage = add_submenu_page("dsidxpress", "dsIDXpress Options", "Options", "manage_options", "dsidxpress-options", array("dsSearchAgent_Admin", "EditOptions"));
			add_action("admin_print_scripts-{$optionsPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}

		add_filter("mce_external_plugins", array("dsSearchAgent_Admin", "AddTinyMcePlugin"));
		add_filter("mce_buttons", array("dsSearchAgent_Admin", "RegisterTinyMceButton"));
		// won't work until this <http://core.trac.wordpress.org/ticket/12207> is fixed
		//add_filter("tiny_mce_before_init", array("dsSearchAgent_Admin", "ModifyTinyMceSettings"));
	}
	static function AddTinyMcePlugin($plugins) {
		$plugins["idxlisting"] = DSIDXPRESS_PLUGIN_URL . "tinymce/single_listing/editor_plugin.js";
		$plugins["idxlistings"] = DSIDXPRESS_PLUGIN_URL . "tinymce/multi_listings/editor_plugin.js";
		return $plugins;
	}
	static function RegisterTinyMceButton($buttons) {
		array_push($buttons, "separator", "idxlisting", "idxlistings");
		return $buttons;
	}
	static function ModifyTinyMceSettings($settings) {
		$settings["wordpress_adv_hidden"] = 0;
		return $settings;
	}
	static function Initialize() {
		register_setting("dsidxpress_activation", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_options", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_options", DSIDXPRESS_API_OPTIONS_NAME, array("dsSearchAgent_Admin", "SanitizeApiOptions"));
	}
	static function LoadHeader() {
		if (self::$HeaderLoaded)
			return;

		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		echo <<<HTML
			<link rel="stylesheet" href="{$pluginUrl}css/admin-options.css" type="text/css" />
HTML;
		self::$HeaderLoaded = true;
	}
	static function DisplayAdminNotices() {
		if (!current_user_can("manage_options"))
			return;

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		if (!isset($options["PrivateApiKey"])) {
			echo <<<HTML
				<div class="error">
					<p style="line-height: 1.6;">
						In order to use the dsIDXpress plugin, you need to add your
						<a href="http://www.dsidxpress.com/tryit/" target="_blank">activation key</a> to the
						<a href="admin.php?page=dsidxpress">dsIDXpress activation area</a>.
					</p>
				</div>
HTML;
		} else if (isset($options["PrivateApiKey"]) && empty($options["Activated"])) {
			echo <<<HTML
				<div class="error">
					<p style="line-height: 1.6;">
						It looks like there may be a problem with the dsIDXpress that's installed on this blog.
						Please take a look at the <a href="admin.php?page=dsidxpress">dsIDXpress diagnostics area</a>
						to find out more about any potential issues
					</p>
				</div>
HTML;
		} else if (isset($options["Activated"]) && empty($options["HideIntroNotification"])) {
			wp_nonce_field("dsidxpress-dismiss-notification", "dsidxpress-dismiss-notification", false);
			echo <<<HTML
				<script>
					function dsidxpressDismiss() {
						jQuery.post(ajaxurl, {
							action: 'dsidxpress-dismiss-notification',
							_ajax_nonce: jQuery('#dsidxpress-dismiss-notification').val()
						});
						jQuery('#dsidxpress-intro-notification').slideUp();
					}
				</script>
				<div id="dsidxpress-intro-notification" class="updated">
					<p style="line-height: 1.6;">Now that you have the <strong>dsIDXpress plugin</strong>
						activated, you'll probably want to start adding <strong>live MLS content</strong>
						to your site right away. The easiest way to get started is to use the three new IDX widgets that have
						been added to your <a href="widgets.php">widgets page</a> and the two new IDX icons
						(they look like property markers) that have been added to the visual editor for
						all of your <a href="page-new.php">pages</a> and <a href="post-new.php">posts</a>.
						You'll probably also want to check out our <a href="http://wiki.dsidxpress.com/wiki:link-structure"
							target="_blank">dsIDXpress virtual page link structure guide</a> so that you
						can start linking to the property listings and property details pages throughout
						your blog.
					</p>
					<p style="line-height: 1.6; text-align: center; font-weight: bold;">Take a look at the
						<a href="http://wiki.dsidxpress.com/wiki:getting-started" target="_blank">dsIDXpress getting
						started guide</a> for more info.
					</p>
					<p style="text-align: right;">(<a href="javascript:void(0)" onclick="dsidxpressDismiss()">dismiss this message</a>)</p>
				</div>
HTML;
		}
	}
	static function DismissNotification() {
		$action = $_POST["action"];
		check_ajax_referer($action);

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$options["HideIntroNotification"] = true;
		update_option(DSIDXPRESS_OPTION_NAME, $options);
		die();
	}
	static function EditOptions() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);

		$urlBase = get_bloginfo("url");
		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
		$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug();
?>
	<div class="wrap metabox-holder">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2>dsIDXpress Options</h2>

		<form method="post" action="options.php">
			<?php settings_fields("dsidxpress_options"); ?>

			<h4>Display Settings</h4>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-DetailsTemplate">Template for details pages:</label>
					</th>
					<td>
						<select id="dsidxpress-DetailsTemplate" name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[DetailsTemplate]">
							<option value="">- Default -</option>
							<?php page_template_dropdown($options["DetailsTemplate"]) ?>
						</select><br />
						<span class="description">Some themes have custom templates that can change how a particular page is displayed. If your theme does have multiple templates, you'll be able to select which one you want to use in the drop-down above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-ResultsTemplate">Template for results pages:</label>
					</th>
					<td>
						<select id="dsidxpress-ResultsTemplate" name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[ResultsTemplate]">
							<option value="">- Default -</option>
							<?php page_template_dropdown($options["ResultsTemplate"]) ?>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-AdvancedTemplate">Template for dsSearchAgent:</label>
					</th>
					<td>
						<select id="dsidxpress-AdvancedTemplate" name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[AdvancedTemplate]">
							<option value="">- Default -</option>
							<?php page_template_dropdown($options["AdvancedTemplate"]) ?>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-CustomTitleText">Title for results pages:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-CustomTitleText" maxlength="49" name="<?php echo DSIDXPRESS_API_OPTIONS_NAME; ?>[CustomTitleText]" value="<?php echo $account_options->CustomTitleText; ?>" /><br />
						<span class="description">By default, the titles are auto-generated based on the type of area searched. You can override this above; use <code>%title%</code> to designate where you want the location title. For example, you could use <code>Real estate in the %title%</code>.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-ResultsMapDefaultState">Default for the Results Map:</label>
					</th>
					<td>
						Open <input type="radio" id="dsidxpress-ResultsMapDefaultState-Open" name="<?php echo DSIDXPRESS_OPTION_NAME; ?>[ResultsMapDefaultState]" value="open" <?php echo $options["ResultsMapDefaultState"] == "open" ? "checked=\"checked\"" : "" ?> />&nbsp;or&nbsp;
						Closed <input type="radio" id="dsidxpress-ResultsMapDefaultState-Closed" name="<?php echo DSIDXPRESS_OPTION_NAME; ?>[ResultsMapDefaultState]" value="closed" <?php echo $options["ResultsMapDefaultState"] == "closed" || !isset($options["ResultsMapDefaultState"]) ? "checked=\"checked\"" : "" ?>/>
					</td>
				</tr>
			</table>

			<h4>Contact Information</h4>
			<span class="description">This information is used in identifying you to the website visitor. For example: Listing PDF Printouts, Contact Forms, and Dwellicious</span>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-FirstName">First Name:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-FirstName" maxlength="49" name="<?php echo DSIDXPRESS_API_OPTIONS_NAME; ?>[FirstName]" value="<?php echo $account_options->FirstName; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-LastName">Last Name:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-LastName" maxlength="49" name="<?php echo DSIDXPRESS_API_OPTIONS_NAME; ?>[LastName]" value="<?php echo $account_options->LastName; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-Email">Email:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-Email" maxlength="49" name="<?php echo DSIDXPRESS_API_OPTIONS_NAME; ?>[Email]" value="<?php echo $account_options->Email; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
			</table>

			<h4>XML Sitemaps Locations</h4>
			<?php if ( in_array('google-sitemap-generator/sitemap.php', get_option('active_plugins'))) {?>
			<span class="description">Add the Locations (City, Community, Tract, or Zip) to your XML Sitemap by adding them via the dialogs below.</span>
			<div class="dsidxpress-SitemapLocations stuffbox">
				<script>dsIDXpressOptions.UrlBase = '<?php echo $urlBase; ?>'; dsIDXpressOptions.OptionPrefix = '<?php echo DSIDXPRESS_OPTION_NAME; ?>';</script>
				<h3><span class="hndle">Locations for Sitemap</span></h3>
				<div class="inside">
					<ul id="dsidxpress-SitemapLocations">
					<?php
						if (isset($options["SitemapLocations"]) && is_array($options["SitemapLocations"])) {
							$location_index = 0;

							usort($options["SitemapLocations"], array("dsSearchAgent_Admin", "CompareListObjects"));

							foreach ($options["SitemapLocations"] as $key => $value) {
								$location_sanitized = urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $value["value"])));
								?>
								<li class="ui-state-default dsidxpress-SitemapLocation">
									<div class="arrow"><span class="dsidxpress-up_down"></span></div>
									<div class="value">
										<a href="<?php echo $urlBase . $value["type"] .'/'. $location_sanitized;?>" target="_blank"><?php echo $value["value"]; ?></a>
										<input type="hidden" name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[SitemapLocations][<?php echo $location_index; ?>][value]" value="<?php echo $value["value"]; ?>" />
									</div>
									<div class="priority">
										Priority: <select name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[SitemapLocations][<?php echo $location_index; ?>][priority]">
											<option value="0.0"<?php echo ($value["priority"] == "0.0" ? ' selected="selected"' : '') ?>>0.0</option>
											<option value="0.1"<?php echo ($value["priority"] == "0.1" ? ' selected="selected"' : '') ?>>0.1</option>
											<option value="0.2"<?php echo ($value["priority"] == "0.2" ? ' selected="selected"' : '') ?>>0.2</option>
											<option value="0.3"<?php echo ($value["priority"] == "0.3" ? ' selected="selected"' : '') ?>>0.3</option>
											<option value="0.4"<?php echo ($value["priority"] == "0.4" ? ' selected="selected"' : '') ?>>0.4</option>
											<option value="0.5"<?php echo ($value["priority"] == "0.5" || !isset($value["priority"]) ? ' selected="selected"' : '') ?>>0.5</option>
											<option value="0.6"<?php echo ($value["priority"] == "0.6" ? ' selected="selected"' : '') ?>>0.6</option>
											<option value="0.7"<?php echo ($value["priority"] == "0.7" ? ' selected="selected"' : '') ?>>0.7</option>
											<option value="0.8"<?php echo ($value["priority"] == "0.8" ? ' selected="selected"' : '') ?>>0.8</option>
											<option value="0.9"<?php echo ($value["priority"] == "0.9" ? ' selected="selected"' : '') ?>>0.9</option>
											<option value="1.0"<?php echo ($value["priority"] == "1.0" ? ' selected="selected"' : '') ?>>1.0</option>
										</select>
									</div>
									<div class="type"><select name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[SitemapLocations][<?php echo $location_index; ?>][type]">
										<option value="city"<?php echo ($value["type"] == "city" ? ' selected="selected"' : ''); ?>>City</option>
										<option value="community"<?php echo ($value["type"] == "community" ? ' selected="selected"' : ''); ?>>Community</option>
										<option value="tract"<?php echo ($value["type"] == "tract" ? ' selected="selected"' : ''); ?>>Tract</option>
										<option value="zip"<?php echo ($value["type"] == "zip" ? ' selected="selected"' : ''); ?>>Zip Code</option>
									</select></div>
									<div class="action"><input type="button" value="Remove" class="button" onclick="dsIDXpressOptions.RemoveSitemapLocation(this)" /></div>
									<div style="clear:both"></div>
								</li>
								<?php
								$location_index++;
							}
						}
					?>
					</ul>

					<div class="dsidxpress-SitemapLocationsNew">
						<div class="arrow">New:</div>
						<div class="value"><input type="text" id="dsidxpress-NewSitemapLocation" maxlength="49" value="" /></div>
						<div class="type">
							<select class="widefat" id="dsidxpress-NewSitemapLocationType"">
								<option value="city">City</option>
								<option value="community">Community</option>
								<option value="tract">Tract</option>
								<option value="zip">Zip Code</option>
							</select>
						</div>
						<div class="action">
							<input type="button" class="button" id="dsidxpress-NewSitemapLocationAdd" value="Add" onclick="dsIDXpressOptions.AddSitemapLocation()" />
						</div>
						<div style="clear:both"></div>
					</div>
				</div>
			</div>

			<span class="description">"Priority" gives a hint to the web crawler as to what you think the importance of each page is. <code>1</code> being highest and <code>0</code> lowest.</span>

			<h4>XML Sitemaps Options</h4>
			<table class="form-table">
				<tr>
					<th>
						<label for="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[SitemapFrequency]">Frequency:</label>
					</th>
					<td>
						<select name="<?php echo DSIDXPRESS_OPTION_NAME ; ?>[SitemapFrequency]" id="<?php echo DSIDXPRESS_OPTION_NAME ; ?>_SitemapFrequency">
							<!--<option value="always"<?php echo ($options["SitemapFrequency"] == "always" ? ' selected="selected"' : '') ?>>Always</option> -->
							<option value="hourly"<?php echo ($options["SitemapFrequency"] == "hourly" ? 'selected="selected"' : '') ?>>Hourly</option>
							<option value="daily"<?php echo ($options["SitemapFrequency"] == "daily" || !isset($options["SitemapFrequency"]) ? 'selected="selected"' : '') ?>>Daily</option>
							<!--<option value="weekly"<?php echo ($options["SitemapFrequency"] == "weekly" ? 'selected="selected"' : '') ?>>Weekly</option>
							<option value="monthly"<?php echo ($options["SitemapFrequency"] == "monthly" ? 'selected="selected"' : '') ?>>Monthly</option>
							<option value="yearly"<?php echo ($options["SitemapFrequency"] == "yearly" ? 'selected="selected"' : '') ?>>Yearly</option>
							<option value="never"<?php echo ($options["SitemapFrequency"] == "never" ? 'selected="selected"' : '') ?>>Never</option> -->
						</select>
						<span class="description">The "hint" to send to the crawler. This does not guarantee frequency, crawler will do what they want.</span>
					</td>
				</tr>
			</table>
			<?php } else { ?>
				<span class="description">To enable this functionality, install and activate this plugin: <a class="thickbox onclick" title="Google XML Sitemaps" href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=google-sitemap-generator&TB_iframe=true&width=640')?>" target="_blank">Google XML Sitemaps</a></span>
			<?php }?>
			<p class="submit">
				<input type="submit" class="button-primary" name="Submit" value="Save Options and Sitemaps" />
			</p>
		</form>
	</div><?php
	}

	static function Activation() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if ($options["PrivateApiKey"]) {
			$diagnostics = self::RunDiagnostics($options);
			$previouslyActive = $options["Activated"];
			$options["Activated"] = $diagnostics["DiagnosticsSuccessful"];
			$options["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];
			if ($previouslyActive != $options["Activated"])
				update_option(DSIDXPRESS_OPTION_NAME, $options);

			$formattedApiKey = $options["AccountID"] . "/" . $options["SearchSetupID"] . "/" . $options["PrivateApiKey"];
		}
?>
	<div class="wrap metabox-holder">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2>dsIDXpress Activation</h2>
		<form method="post" action="options.php">
			<?php settings_fields("dsidxpress_activation"); ?>
			<h3>Plugin activation</h3>
			<p>
				In order to use <i><a href="http://www.dsidxpress.com/" target="_blank">dsIDXpress</a></i>
				to display real estate listings from the MLS on your blog, you must have an activation key from
				<a href="http://www.diversesolutions.com/" target="_blank">Diverse Solutions</a>. Without it, the plugin itself
				will be useless, widgets won't appear, and all "shortcodes" specific to this plugin in your post and page
				content will be hidden when that content is displayed on your blog. If you already have this activation key, enter it
				below and you can be on your way.
			</p>
			<p>
				If you <b>don't</b> yet have an activation key, you can purchase one from us
				(<a href="http://www.diversesolutions.com/" target="_blank">Diverse Solutions</a>) for a monthly price that
				varies depending on the MLS you belong to. Furthermore, in order for us to authorize the data to be transferred
				from us to your blog, you <b>must</b> be a member of the MLS you would like the data for. In some cases, you
				even have to be a real estate broker (or have your broker sign off on your request for this data). If you're 1)
				a real estate agent, and 2) a member of an MLS, and you're interested in finding out more, please
				<a href="http://www.dsidxpress.com/contact/" target="_blank">contact us</a>.
			</p>
			<div id="dsidx-activation-notice">
				<p>
					By default, <strong>your activation key will only work on one blog at a time</strong>. If you'd like to make it
					work on more than one blog, you need to <a href="http://www.dsidxpress.com/contact/" target="_blank">contact our sales department</a>.
				</p>
				<p>
					<strong>If you activate dsIDXpress on this blog, dsIDXpress will immediately stop working on any other blogs you use
					this plugin on!</strong>
				</p>
			</div>
			<table class="form-table">
				<tr>
					<th style="width: 110px;">
						<label for="option-FullApiKey">Activation key:</label>
					</th>
					<td>
						<input type="text" id="option-FullApiKey" maxlength="49" name="<?php echo DSIDXPRESS_OPTION_NAME; ?>[FullApiKey]" value="<?php echo $formattedApiKey ?>" />
					</td>
				</tr>
				<tr>
					<th style="width: 110px;">Current status:</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["DiagnosticsSuccessful"] ? "success" : "failure" ?>">
						** <?php echo $diagnostics && $diagnostics["DiagnosticsSuccessful"] ? "ACTIVE" : "INACTIVE" ?> **
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" name="Submit" value="Activate Plugin For This Blog / Server" />
			</p>

<?php
		if ($diagnostics) {
?>
			<h3>Diagnostics</h3>
<?php
			if (isset($diagnostics["error"])) {
?>
			<p class="error">
				It seems that there was an issue while trying to load the diagnostics from Diverse Solutions' servers. It's possible that our servers
				are temporarily down, so please check back in just a minute. If this problem persists, please
				<a href="http://www.diversesolutions.com/support.htm" target="_blank">contact us</a>.
			</p>
<?php
			} else {
?>
			<table class="form-table" style="margin-bottom: 15px;">
				<tr>
					<th style="width: 230px;">
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Account%20active#diagnostics" target="_blank">Account active?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountValid"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsAccountValid"] ? "Yes" : "No" ?>
					</td>

					<th style="width: 290px;">
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Activation%20key%20active#diagnostics" target="_blank">Activation key active?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyValid"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyValid"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Account%20authorized%20for%20this%20MLS#diagnostics" target="_blank">Account authorized for this MLS?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "Yes" : "No" ?>
					</td>

					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Activation%20key%20authorized%20for%20this%20blog#diagnostics" target="_blank">Activation key authorized for this blog?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Clock%20accurate%20on%20this%20server#diagnostics" target="_blank">Clock accurate on this server?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["ClockIsAccurate"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["ClockIsAccurate"] ? "Yes" : "No" ?>
					</td>

					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Activation%20key%20authorized%20for%20this%20server#diagnostics" target="_blank">Activation key authorized for this server?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=WordPress%20link%20structure%20ok#diagnostics" target="_blank">WordPress link structure ok?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UrlInterceptSet"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["UrlInterceptSet"] ? "Yes" : "No" ?>
					</td>

					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Under%20monthly%20API%20call%20limit#diagnostics" target="_blank">Under monthly API call limit?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Server%20PHP%20version%20at%20least%205.2#diagnostics" target="_blank">Server PHP version at least 5.2?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["PhpVersionAcceptable"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["PhpVersionAcceptable"] ? "Yes" : "No" ?>
					</td>

					<th>
						<a href="http://wiki.dsidxpress.com/wiki:installing?s[]=Would%20you%20like%20fries%20with%20that#diagnostics" target="_blank">Would you like fries with that?</a>
					</th>
					<td class="dsidx-status dsidx-success">
						Yes <!-- you kidding? we ALWAYS want fries. mmmm, friessssss -->
					</td>
				</tr>
			</table>
<?php
			}
		}
?>
		</form>
	</div>
<?php
	}
	static function RunDiagnostics($options) {
		// it's possible for a malicious script to trick a blog owner's browser into running the Diagnostics which passes the PrivateApiKey which
		// could allow a bug on the wire to pick up the key, but 1) we have IP and URL restrictions, and 2) there are much bigger issues than the
		// key going over the wire in the clear if the traffic is being spied on in the first place
		global $wp_rewrite;

		$diagnostics = dsSearchAgent_ApiRequest::FetchData("Diagnostics", array("apiKey" => $options["PrivateApiKey"]), false, 0, $options);

		if (empty($diagnostics["body"]) || $diagnostics["response"]["code"] != "200")
			return array("error" => true);

		$diagnostics = (array)json_decode($diagnostics["body"]);
		$setDiagnostics = array();
		$timeDiff = time() - strtotime($diagnostics["CurrentServerTimeUtc"]);
		$secondsIn2Hrs = 60 * 60 * 2;
		$permalinkStructure = get_option("permalink_structure");

		$setDiagnostics["IsApiKeyValid"] = $diagnostics["IsApiKeyValid"];
		$setDiagnostics["IsAccountAuthorizedToMLS"] = $diagnostics["IsAccountAuthorizedToMLS"];
		$setDiagnostics["IsAccountValid"] = $diagnostics["IsAccountValid"];
		$setDiagnostics["IsApiKeyAuthorizedToUri"] = $diagnostics["IsApiKeyAuthorizedToUri"];
		$setDiagnostics["IsApiKeyAuthorizedToIP"] = $diagnostics["IsApiKeyAuthorizedToIP"];

		$setDiagnostics["PhpVersionAcceptable"] = version_compare(phpversion(), DSIDXPRESS_MIN_VERSION_PHP) != -1;
		$setDiagnostics["UrlInterceptSet"] = get_option("permalink_structure") != "" && !preg_match("/index\.php/", $permalinkStructure);
		$setDiagnostics["ClockIsAccurate"] = $timeDiff < $secondsIn2Hrs && $timeDiff > -1 * $secondsIn2Hrs;
		$setDiagnostics["UnderMonthlyCallLimit"] = $diagnostics["AllowedApiRequestCount"] === 0 || $diagnostics["AllowedApiRequestCount"] > $diagnostics["CurrentApiRequestCount"];

		$setDiagnostics["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];

		$setDiagnostics["DiagnosticsSuccessful"] = true;
		foreach ($setDiagnostics as $key => $value) {
			if (!$value && $key != "HasSearchAgentPro")
				$setDiagnostics["DiagnosticsSuccessful"] = false;
		}
		$wp_rewrite->flush_rules();

		return $setDiagnostics;
	}
	static function SanitizeOptions($options) {
		if (isset($options["FullApiKey"])) {
			$options["FullApiKey"] = trim($options["FullApiKey"]);
			$apiKeyParts = explode("/", $options["FullApiKey"]);
			unset($options["FullApiKey"]);

			$options["AccountID"] = $apiKeyParts[0];
			$options["SearchSetupID"] = $apiKeyParts[1];
			$options["PrivateApiKey"] = $apiKeyParts[2];

			dsSearchAgent_ApiRequest::FetchData("BindToRequester", array(), false, 0, $options);
			$diagnostics = self::RunDiagnostics($options);
			$options["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];
			$options["Activated"] = $diagnostics["DiagnosticsSuccessful"];

			if (!$options["Activated"] && isset($options["HideIntroNotification"]))
				unset($options["HideIntroNotification"]);
		}
		// different option pages fill in different parts of this options array, so we simply merge what's already there with our new data
		if (get_option(DSIDXPRESS_OPTION_NAME))
			$options = array_merge(get_option(DSIDXPRESS_OPTION_NAME), $options);

		// call the sitemap rebuild action since they may have changed their sitemap locations. the documentation says that the sitemap
		// may not be rebuilt immediately but instead scheduled into a cron job for performance reasons.
		do_action("sm_rebuild");

		return $options;
	}

	/*
	 * We're using the sanitize to capture the POST for these options so we can send them back to the diverse API
	 * since we save and consume -most- options there.
	 */
	static function SanitizeApiOptions($options) {
		if (is_array($options)) {
			$options_text = "";

			foreach ($options as $key => $value) {
				if ($options_text != "") $options_text .= ",";
				$options_text .= $key.'|'.urlencode($value);
				unset($options[$key]);
			}

			$result = dsSearchAgent_ApiRequest::FetchData("SaveAccountOptions", array("options" => $options_text), false, 0);
		}
		return $options;
	}

	static function CompareListObjects($a, $b)
    {
        $al = strtolower($a["value"]);
        $bl = strtolower($b["value"]);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }
}
?>