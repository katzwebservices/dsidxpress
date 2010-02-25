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

$referring_url = $_SERVER['HTTP_REFERER'];
$post_vars = $_POST;
$post_vars["referringURL"] = $referring_url;

$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ContactForm", $post_vars, false, 0);

if (false && $_POST["returnToReferrer"] == "1") {
	$post_response = json_decode($apiHttpResponse["body"]);
	
	if ($post_response->Error == 1)
		$redirect_url = $referring_url .'?dsformerror='. $post_response->Message;
	else 
		$redirect_url = $referring_url;
	
	header( 'Location: '. $redirect_url ) ;
} else {
	echo $apiHttpResponse["body"];
}
?>