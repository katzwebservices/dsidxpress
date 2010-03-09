<?php
//bootstrap wordpress
$bootstrapSearchDir = dirname($_SERVER["SCRIPT_FILENAME"]);
$appPhysicalPath = $_SERVER["APPL_PHYSICAL_PATH"];
$docRoot = dirname(isset($appPhysicalPath) ? $appPhysicalPath : $_SERVER["DOCUMENT_ROOT"]);

while (!file_exists($bootstrapSearchDir . "/wp-load.php")) {
	$bootstrapSearchDir = dirname($bootstrapSearchDir);
	if (strpos($bootstrapSearchDir, $docRoot) === false)
		break;
}
require_once($bootstrapSearchDir . "/wp-load.php");

class dsSearchAgent_ClientAssist {
	static function SlideshowXml() {
		$uriSuffix = '';
		if (array_key_exists('uriSuffix', $_GET))
			$uriSuffix = $_GET['uriSuffix'];
		
		$urlBase = $_GET['uriBase'];
		
		if (!preg_match("/^http:\/\//", $urlBase))
			$urlBase = "http://" . $urlBase;
		$urlBase = str_replace(array('&', '"'), array('&amp;', '&quot;'), $urlBase);
		
		header('Content-Type: text/xml');
		echo '<?xml version="1.0"?><gallery><album lgpath="' . $urlBase . '" fspath="' . $urlBase . '">';
		for($i = 0; $i < (int)$_GET['count']; $i++) {
			echo '<img src="' . $i . '-medium.jpg' . $uriSuffix . '" fs="' . $i . '-full.jpg' . $uriSuffix . '" />';
		}
		echo '</album></gallery>';
	}
	static function EmailFriendForm() {
		$referring_url = $_SERVER['HTTP_REFERER'];
		$post_vars = $_POST;
		$post_vars["referringURL"] = $referring_url;
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EmailFriendForm", $post_vars, false, 0);
		
		echo $apiHttpResponse["body"];
		die();
	}
	static function ContactForm(){
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
			die();
		} else {
			echo $apiHttpResponse["body"];
			die();
		}
	}
	static function PrintListing(){
		if($_REQUEST["PropertyID"]) $apiParams["query.PropertyID"] = $_REQUEST["PropertyID"];		
		if($_REQUEST["MlsNumber"]) $apiParams["query.MlsNumber"] = $_REQUEST["MlsNumber"];
		$apiParams["responseDirective.ViewNameSuffix"] = "printpdf";
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiParams, false);
		
		header('Content-type: application/pdf');
		echo($apiHttpResponse["body"]);	
		
		die();
	}
}
call_user_func(array('dsSearchAgent_ClientAssist',  $_REQUEST['action']));
?>