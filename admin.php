<?php
add_action("admin_init", "dsSearchAgent_Admin::Initialize");
add_action("admin_menu", "dsSearchAgent_Admin::AddMenu");

class dsSearchAgent_Admin {
	static function AddMenu() {
		$optionsPage = add_options_page("dsIDXpress Options", "dsIDXpress", "manage_options", "dsidxpress", "dsSearchAgent_Admin::EditOptions");
		add_action("admin_print_scripts-{$optionsPage}", "dsSearchAgent_Admin::LoadHeader");
		
		//add_filter("mce_external_plugins", "dsSearchAgent_Admin::AddTinyMcePlugin");
		//add_filter("mce_buttons", "dsSearchAgent_Admin::RegisterTinyMceButton");
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
	static function Initialize() {
		register_setting("dsidxpress", DSIDXPRESS_OPTION_NAME, "dsSearchAgent_Admin::SanitizeOptions");
		register_setting("dsidxpress", "dsidxpress-api-options", "dsSearchAgent_Admin::SanitizeApiOptions");
	}
	static function LoadHeader() {
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		
		echo <<<HTML
			<link rel="stylesheet" href="{$pluginUrl}css/admin-options.css" type="text/css" />
HTML;
	}
	static function EditOptions() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		
		if ($options["PrivateApiKey"]) {
			$diagnostics = self::RunDiagnostics($options);
			$formattedApiKey = $options["AccountID"] . "/" . $options["SearchSetupID"] . "/" . $options["PrivateApiKey"];
		}
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		
		if ($apiHttpResponse["response"]["code"] == "404")
			return array();
		else if (!empty($apiHttpResponse["errors"]) || substr($apiHttpResponse["response"]["code"], 0, 1) == "5")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2>dsIDXpress Options</h2>
		<form method="post" action="options.php">
			<?php settings_fields("dsidxpress"); ?>
			<?php if($diagnostics["DiagnosticsSuccessful"] === true){ ?>
			<h3>Display Settings</h3>
			
			<table class="form-table">
				<tr>
					<th >
						<label for="dsidxpress-CustomTitleText">Custom Title Text:</label>
					</th>
					<td>					
						<input type="text" id="dsidxpress-CustomTitleText" maxlength="49" name="dsidxpress-api-options[CustomTitleText]" value="<?php echo $account_options->CustomTitleText; ?>" />
						<span class="description">use <code>%title%</code> to designate where you want the location title like: <code>Real Estate in %title%</code></span>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" class="button-primary" name="Submit" value="Save Options" />
			</p>
			<?php }?>
			
			
			<h3>Plugin activation</h3>
			<p>
				In order to use <i>dsIDXpress</i>
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
				<a href="http://www.diversesolutions.com/" target="_blank">contact us</a>.
			</p>
			<table class="form-table">
				<tr>
					<th style="width: 110px;">
						<label for="option-FullApiKey">Activation key:</label>
					</th>
					<td>
						<input type="text" id="option-FullApiKey" maxlength="49" name="dssearchagent-wordpress-edition[FullApiKey]" value="<?php echo $formattedApiKey ?>" />
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
			if ($diagnostics["error"]) {
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
					<th style="width: 230px;">Account active?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountValid"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsAccountValid"] ? "Yes" : "No" ?>
					</td>
					
					<th style="width: 290px;">Activation key active?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyValid"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyValid"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>Account authorized for this MLS?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "Yes" : "No" ?>
					</td>
					
					<th>Activation key authorized for this blog?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>Clock accurate on this server?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["ClockIsAccurate"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["ClockIsAccurate"] ? "Yes" : "No" ?>
					</td>
					
					<th>Activation key authorized for this server?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>WordPress link structure ok?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UrlInterceptSet"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["UrlInterceptSet"] ? "Yes" : "No" ?>
					</td>
					
					<th>Under monthly API call limit?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "Yes" : "No" ?>
					</td>
				</tr>
				<tr>
					<th>Server PHP version at least 5.2?</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["PhpVersionAcceptable"] ? "success" : "failure" ?>">
						<?php echo $diagnostics["PhpVersionAcceptable"] ? "Yes" : "No" ?>
					</td>
					
					<th>Would you like fries with that?</th>
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
		
		$diagnostics = dsSearchAgent_ApiRequest::FetchData("Diagnostics", array("apiKey" => $options["PrivateApiKey"]), false, 0);
		if (empty($diagnostics["body"]) || $diagnostics["response"]["code"] != "200")
			return array("error" => true);
		
		$diagnostics = (array)json_decode($diagnostics["body"]);
		$setDiagnostics = array();
		$timeDiff = time() - strtotime($diagnostics["CurrentServerTimeUtc"]);
		$secondsIn2Hrs = 60 * 60 * 2;
		
		$setDiagnostics["IsApiKeyValid"] = $diagnostics["IsApiKeyValid"];
		$setDiagnostics["IsAccountAuthorizedToMLS"] = $diagnostics["IsAccountAuthorizedToMLS"];
		$setDiagnostics["IsAccountValid"] = $diagnostics["IsAccountValid"];
		$setDiagnostics["IsApiKeyAuthorizedToUri"] = $diagnostics["IsApiKeyAuthorizedToUri"];
		$setDiagnostics["IsApiKeyAuthorizedToIP"] = $diagnostics["IsApiKeyAuthorizedToIP"];
		
		$setDiagnostics["PhpVersionAcceptable"] = version_compare(phpversion(), DSIDXPRESS_MIN_VERSION_PHP) != -1;
		$setDiagnostics["UrlInterceptSet"] = get_option("permalink_structure") != "";
		$setDiagnostics["ClockIsAccurate"] = $timeDiff < $secondsIn2Hrs && $timeDiff > -1 * $secondsIn2Hrs;
		$setDiagnostics["UnderMonthlyCallLimit"] = $diagnostics["AllowedApiRequestCount"] === 0 || $diagnostics["AllowedApiRequestCount"] > $diagnostics["CurrentApiRequestCount"];
		
		$setDiagnostics["DiagnosticsSuccessful"] = true;
		foreach ($setDiagnostics as $key => $value) {
			if (!$value)
				$setDiagnostics["DiagnosticsSuccessful"] = false;
		}
		
		$options["Activated"] = $setDiagnostics["DiagnosticsSuccessful"];
		update_option(DSIDXPRESS_OPTION_NAME, $options);
		$wp_rewrite->flush_rules();
		
		return $setDiagnostics;
	}
	static function SanitizeOptions($options) {
		if ($options["FullApiKey"]) {
			$apiKeyParts = explode("/", $options["FullApiKey"]);
	
			$options["AccountID"] = $apiKeyParts[0];
			$options["SearchSetupID"] = $apiKeyParts[1];
			$options["PrivateApiKey"] = $apiKeyParts[2];
	
			dsSearchAgent_ApiRequest::FetchData("BindToRequester", array(), false, 0, $options);
			
			unset($options["FullApiKey"]);
		}
		return $options;
	}
	
	/*
	 * We're using the sanitize to capture the POST for these options so we can send them back to the diverse API
	 * since we save and consume -most- options there.
	 */
	static function SanitizeApiOptions($options){
		$options_text = "";
		
		foreach($options as $key => $value){
			if($options_text != "") $options_text .= ",";
			$options_text .= $key.'|'.urlencode($value);
			unset($options[$key]);
		}
		
		$result = dsSearchAgent_ApiRequest::FetchData("SaveAccountOptions", array("options" => $options_text), false, 0);
		
		return $options;
	}
}
?>