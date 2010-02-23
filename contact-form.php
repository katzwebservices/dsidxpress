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

$options = get_option(DSIDXPRESS_OPTION_NAME);
$request = new WP_Http();
$requestUri = dsSearchAgent_ApiRequest::$ApiEndPoint . "ContactForm";
$apiHttpResponse = (array)$request->post($requestUri, array(
	"body"			=> array(
		/*searchSetupID	=> $options["SearchSetupID"],
		type			=> $_REQUEST["type"]*/
	),
	"httpversion"	=> "1.1",
	"redirection"	=> "0"
));
$locations = json_decode($apiHttpResponse["body"]);
?>