<?php
// bootstrap our wordpress instance
$bootstrapSearchDir = dirname($_SERVER["SCRIPT_FILENAME"]);
$docRoot = $_SERVER["DOCUMENT_ROOT"];

while (!file_exists($bootstrapSearchDir . "/wp-load.php")) {
	$bootstrapSearchDir = dirname($bootstrapSearchDir);
	if (strpos($bootstrapSearchDir, $docRoot) === false)
		break;
}
require_once($bootstrapSearchDir . "/wp-load.php");
require_once($bootstrapSearchDir . "/wp-admin/admin.php");

if (!current_user_can("edit_pages"))
	wp_die("You can't do anything destructive in here, but you shouldn't be playing around with this anyway.");

global $wp_version, $tinymce_version;

$localJsUri = get_option("siteurl") . "/" . WPINC . "/js/";
$options = get_option(DSIDXPRESS_OPTION_NAME);

$propertyTypes = dsSearchAgent_ApiRequest::FetchData("AccountSearchSetupPropertyTypes", array(), false, 60 * 60 * 24);
$propertyTypes = json_decode($propertyTypes["body"]);

$availableLinks = dsSearchAgent_ApiRequest::FetchData("AccountAvailableLinks", array(), false, 0);
$availableLinks = json_decode($availableLinks["body"]);

?>

<!DOCTYPE html>
<html>
<head>
	<title>dsIDXpress: Insert Properties</title>

	<script src="<?php echo $localJsUri ?>tinymce/tiny_mce_popup.js?ver=<?php echo urlencode($tinymce_version) ?>"></script>
	<script src="<?php echo $localJsUri ?>tinymce/utils/mctabs.js?ver=<?php echo urlencode($tinymce_version) ?>"></script>
	<!-- jsonpCallback $.ajax arg didn't seem to work w/ WP's version of jquery... -->
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.0/jquery.min.js"></script>
	<script src="js/dialog.js?ver=<?php echo urlencode(DSIDXPRESS_PLUGIN_VERSION) ?>"></script>
	<script>
		var ApiRequest = {
			uriBase: '<?php echo dsSearchAgent_ApiRequest::$ApiEndPoint ?>',
			searchSetupID: <?php echo $options["SearchSetupID"] ?>,
		};
	</script>

	<style type="text/css">
		* {
			font-family:Verdana,Arial;
			font-size:10px;
			line-height:15px;
		}
		p {
			margin: 0 0 15px;
		}
		#insert, #cancel, #apply, .mceActionPanel .button, input.mceButton, .updateButton {
			width: 114px;
		}
		.panel_wrapper {
			padding-top: 13px;
		}
		label {
			cursor: pointer;
		}
		th {
			text-align: left;
			vertical-align: top;
		}
		td {
			padding-bottom: 5px;
		}
		.panel_wrapper div.current {
			height: 120px;
		}
	</style>
</head>
<body>
	<p>
		Using dsIDXpress's Live Listings&#8471; shortcode functionality, you can easily insert real estate listings into any page or blog post.
		The listings will stay updated whether the page/post is viewed hours, weeks, or even years after the page/post is created!
	</p>
	<p>
		In order embed multiple listings into your page/post, you can either create a quick custom search or, if you have
		<a href="http://www.diversesolutions.com/dssearchagent-idx-solution.aspx" target="_blank">dsSearchAgent Pro</a>, use a pre-saved link
		you've already created in your Diverse Solutions Control Panel. Simply choose a tab below, configure the options, and then click "Insert
		Listings" at the bottom.
	</p>

	<div class="tabs">
		<ul>
			<li id="custom_search_tab" class="current"><span><a href="javascript:void(0);" onclick="dsidxMultiListings.changeTab('quick-search')">Quick Search</a></span></li>
			<li id="saved_links_tab"><span><a href="javascript:void(0);" onclick="dsidxMultiListings.changeTab('pre-saved-links')">Pre-saved Links</a></span></li>
		</ul>
	</div>

	<div class="panel_wrapper">
		<div id="custom_search_panel" class="panel current">
			<table>
				<tr>
					<th style="width: 110px;">Area type</th>
					<td>
						<select id="area-type">
							<option value="city">City</option>
							<option value="community">Community</option>
							<option value="tract">Tract</option>
							<option value="zip">Zip</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>Area name</th>
					<td>
						<select id="area-name">
							<option>- dynamic from area type -</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>Price range</th>
					<td>
						<input type="text" id="min-price" style="width: 70px;" />
						-
						<input type="text" id="max-price" style="width: 70px;" />
					</td>
				</tr>
				<tr>
					<th>Property types</th>
					<td>
						<select id="area-name">
							<option value="">- All property types -</option>
<?php
foreach ($propertyTypes as $propertyType) {
	if ($propertyType->IsSearchedByDefault)
		continue;

	$name = htmlentities($propertyType->DisplayName);
	echo "<option value=\"{$propertyType->SearchSetupPropertyTypeID}\">{$name}</option>";
}
?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Display order</th>
					<td>
						<select id="display-order-column">
							<option>Price, highest first</option>
							<option>Home size, largest first</option>
							<option>Lot size, largest first</option>
							<option>Walk Score&trade;, highest first</option>
							<option>Price drop (%), highest first</option>
							<option>Days on market, newest first</option>
							<option>Days on market, oldest first</option>
							<option>Last updated, newest first</option>
							<option>Last updated, oldest first</option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<div id="saved_links_panel" class="panel">
			<p>Select the pre-saved search link that you'd like to use for these results. To create more or edit the
			existing links, you will need to login to the <a href="http://controlpanel.diversesolutions.com/" target="_blank">Diverse Solutions Control Panel</a>.</p>
			<div style="text-align: center;">
				<select id="saved-link">
<?php
foreach ($availableLinks as $link) {
	echo "<option value=\"{$link->LinkID}\" {$selectedLink[$link->LinkID]}>{$link->Title}</option>";
}
?>
				</select>
			</div>
		</div>
	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="insert" name="insert" value="Insert listings" onclick="dsidxSingleListing.insert();" />
		</div>

		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
		</div>
	</div>

</body>
</html>
