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

$referring_url = $_SERVER['HTTP_REFERER'];
$post_vars = $_POST;
$post_vars["referringURL"] = $referring_url;

$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EmailFriendForm", $post_vars, false, 0);

echo $apiHttpResponse["body"];
die();
?>