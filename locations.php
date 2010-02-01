<?php
// bootstrap our wordpress instance
$bootstrapSearchDir = dirname($_SERVER["SCRIPT_FILENAME"]);
$appPhysicalPath = $_SERVER["APPL_PHYSICAL_PATH"];
$docRoot = dirname(isset($appPhysicalPath) ? $appPhysicalPath : $_SERVER["DOCUMENT_ROOT"]);

while (!file_exists($bootstrapSearchDir . "/wp-load.php")) {
	$bootstrapSearchDir = dirname($bootstrapSearchDir);
	if (strpos($bootstrapSearchDir, $docRoot) === false)
		break;
}
require_once($bootstrapSearchDir . "/wp-load.php");
require_once($bootstrapSearchDir . "/wp-admin/admin.php");

if (!current_user_can("edit_pages"))
	wp_die("You can't do anything destructive in here, but you shouldn't be playing around with this anyway.");

$options = get_option(DSIDXPRESS_OPTION_NAME);
$request = new WP_Http();
$requestUri = dsSearchAgent_ApiRequest::$ApiEndPoint . "LocationsByType";
$apiHttpResponse = (array)$request->post($requestUri, array(
	"body"			=> array(
		searchSetupID	=> $options["SearchSetupID"],
		type			=> $_REQUEST["type"]
	),
	"httpversion"	=> "1.1",
	"redirection"	=> "0"
));
$locations = json_decode($apiHttpResponse["body"]);
?>
<!DOCTYPE html>
<html>
<head>
	<style>* { font-family:Verdana; } h2 { font-size: 14px; } body { font-size: 12px; }</style>
</head>
<body>
	<h2>Possible <?php echo ucwords($_REQUEST["type"]); ?> Locations</h2>
<?php
foreach ($locations as $location) {
	?><div><?php echo $location->Name; ?></div><?php
}
?>
	</body>
</html>