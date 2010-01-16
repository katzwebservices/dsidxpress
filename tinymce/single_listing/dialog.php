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
?>

<!DOCTYPE html>
<html>
<head>
	<title>dsIDXpress: Insert Property</title>
	
	<script src="<?php echo $localJsUri ?>tinymce/tiny_mce_popup.js?ver=<?php echo urlencode($tinymce_version) ?>"></script>
	<script src="<?php echo $localJsUri ?>jquery/jquery.js?ver=<?php echo urlencode($wp_version) ?>"></script>
	<script src="js/dialog.js?ver=<?php echo urlencode(DSIDXPRESS_PLUGIN_VERSION) ?>"></script>
	
	<style type="text/css">
		th {
			text-align: left;
			vertical-align: top;
		}
		#data-table td {
			padding-bottom: 5px;
		}
	</style>
</head>
<body>

	<p>
		You can easily harness the power of dsIDXpress to insert a "live" real estate listing into your blog post.
	</p>
	<table id="data-table">
		<tr>
			<th style="width: 100px; padding-top: 2px;"><label for="mls-number">MLS #</label></th>
			<td><input id="mls-number" name="mls-number" type="text" class="text" value="L29273" /></td>
		</tr>
		<tr>
			<th style="padding-top: 7px;">Data to show</th>
			<td>
				<table id="data-show-options">
					<tr>
						<td><label for="show-all">- Everything -</td>
						<td><input type="checkbox" id="show-all" name="show-all" checked="checked" /></td>
					</tr>
					<tr>
						<td><label for="show-price-history">Price History</td>
						<td><input type="checkbox" id="show-price-history" name="show-price-history" checked="checked" disabled="disabled" /></td>
					</tr>
					<tr>
						<td><label for="show-schools">Schools</label></td>
						<td><input type="checkbox" id="show-schools" name="show-schools" checked="checked" disabled="disabled" /></td>
					</tr>
					<tr>
						<td><label for="show-extra-details">Extra Details</td>
						<td><input type="checkbox" id="show-extra-details" name="show-extra-details" checked="checked" disabled="disabled" /></td>
					</tr>
					<tr>
						<td><label for="show-features">Features</td>
						<td><input type="checkbox" id="show-features" name="show-features" checked="checked" disabled="disabled" /></td>
					</tr>
					<tr>
						<td><label for="show-location">Location (Map)</td>
						<td><input type="checkbox" id="show-location" name="show-location" checked="checked" disabled="disabled" /></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="insert" name="insert" value="Insert listing" onclick="dsidxSingleListing.insert();" />
		</div>

		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
		</div>
	</div>

</body>
</html>
