<?php

define('ZP_NO_REDIRECT', true);

//bootstrap wordpress
$bootstrapSearchDir = dirname($_SERVER["SCRIPT_FILENAME"]);
$docRoot = dirname(isset($_SERVER["APPL_PHYSICAL_PATH"]) ? $_SERVER["APPL_PHYSICAL_PATH"] : $_SERVER["DOCUMENT_ROOT"]);

while (!file_exists($bootstrapSearchDir . "/wp-load.php")) {
	$bootstrapSearchDir = dirname($bootstrapSearchDir);
	if (strpos($bootstrapSearchDir, $docRoot) === false){
		$bootstrapSearchDir = "../../.."; // critical failure in our directory finding, so fall back to relative
		break;
	}
}
require_once($bootstrapSearchDir . "/wp-load.php");
if(defined('ZPRESS_API') && ZPRESS_API != '') {
	require_once(WPMU_PLUGIN_DIR . '/akismet/akismet.class.php');
}

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
		echo '<?xml version="1.0"?><gallery><album lgpath="' . $urlBase . '" tnpath="' . $urlBase . '">';
		for($i = 0; $i < (int)$_GET['count']; $i++) {
			echo '<img src="' . $i . '-medium.jpg' . $uriSuffix . '" tn="' . $i . '-medium.jpg' . $uriSuffix . '" link="javascript:dsidx.details.LaunchLargePhoto('. $i .','. $_GET['count'] .',\''. $urlBase .'\',\''. $uriSuffix .'\')" target="_blank" />';
		}
		echo '</album></gallery>';
	}
	static function SlideshowParams() {
		$count = @$_GET['count'];
		$uriSuffix = @$_GET['uriSuffix'];
		$uriBase = @$_GET['uriBase'];

		$slideshow_xml_url = dsSearchAgent_ApiRequest::MakePluginsUrlRelative(plugins_url() . '/dsidxpress/' . "client-assist.php?action=SlideshowXml&count=$count&uriSuffix=$uriSuffix&uriBase=$uriBase");
		$param_xml = file_get_contents('assets/slideshowpro-generic-params.xml');

		$param_xml = str_replace("{xmlFilePath}", htmlspecialchars($slideshow_xml_url), $param_xml);
		$param_xml = str_replace("{imageTitle}", "", $param_xml);

		header('Content-Type: text/xml');
		echo($param_xml);
	}
	static function EmailFriendForm() {
		$referring_url = $_SERVER['HTTP_REFERER'];
		$post_vars = $_POST;
		$post_vars["referringURL"] = $referring_url;

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EmailFriendForm", $post_vars, false, 0);

		echo $apiHttpResponse["body"];
		die();
	}
	static function LoginRecovery(){
		global $curent_site, $current_blog, $blog_id;
		
		$referring_url = $_SERVER['HTTP_REFERER'];
		$post_vars = $_POST;
		$post_vars["referringURL"] = $referring_url;
		$post_vars["domain"] = $current_blog->domain;
		$post_vars["path"] = $current_blog->path;
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("LoginRecovery", $post_vars, false, 0);
		
		echo $apiHttpResponse["body"];
		die();
	}
	static function ResetPassword()
	{
		$referring_url = $_SERVER['HTTP_REFERER'];
		$post_vars = $_POST;
		$post_vars["referringURL"] = $referring_url;

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ResetPassword", $post_vars, false, 0);
		
		echo $apiHttpResponse["body"];
		die();	
	}
	static function ContactForm(){
		$referring_url = @$_SERVER['HTTP_REFERER'];
		$post_vars = $_POST;
		$post_vars["referringURL"] = $referring_url;
		
		//Fix up post vars for Beast ContactForm API
		if (isset($post_vars['name']) && !isset($post_vars['firstName'])) {
			$name = $post_vars['name'];
			$name_split = preg_split('/[\s]+/', $post_vars['name'], 2, PREG_SPLIT_NO_EMPTY);
			$post_vars['firstName'] = count($name_split) > 0 ? $name_split[0] : '';
			$post_vars['lastName'] = count($name_split) > 1 ? $name_split[1] : '';
		}
		if (!isset($post_vars['phoneNumber'])) $post_vars['phoneNumber'] = '';
		
		$message = (!empty($post_vars['scheduleYesNo']) && $post_vars['scheduleYesNo'] == 'on' ? "Schedule showing on {$post_vars['scheduleDateDay']} / {$post_vars['scheduleDateMonth']} " : "Request info ") . 
						@"for ".(!empty($post_vars['propertyStreetAddress']) ? $post_vars['propertyStreetAddress']:"")." ".(!empty($post_vars['propertyCity']) ? $post_vars['propertyCity'] : "").", ".(!empty($post_vars['propertyState']) ? $post_vars['propertyState'] : "")." ".(!empty($post_vars['propertyZip']) ? $post_vars['propertyZip'] : "").
						@". ".$post_vars['comments'];

		if(defined('ZPRESS_API') && ZPRESS_API != ''){
		} else {
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
		header('Content-type: application/json');
		echo '{ "Error": false, "Message": "" }';
		die();
	}
	static function PrintListing(){
		if($_REQUEST["PropertyID"]) $apiParams["query.PropertyID"] = $_REQUEST["PropertyID"];
		if($_REQUEST["MlsNumber"]) $apiParams["query.MlsNumber"] = $_REQUEST["MlsNumber"];
		$apiParams["responseDirective.ViewNameSuffix"] = "printpdf";
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiParams, false);

		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="Property-'. $_REQUEST["MlsNumber"] .'.pdf"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		header('Cache-control: private');
		header('Pragma: private');
		header('X-Robots-Tag: noindex');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

		echo($apiHttpResponse["body"]);

		die();
	}
	static function OnBoard_GetAccessToken(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("OnBoard_GetAccessToken");
		echo $apiHttpResponse["body"];
		die();
	}
	static function Login(){
		$post_vars = $_POST;

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Login", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		
		if($response->Success){			
			$remember = !empty($_POST["remember"]) && $_POST["remember"] == "on" ? time()+60*60*24*30 : 0;
			
			setcookie('dsidx-visitor-public-id', $response->Visitor->PublicID, $remember, '/');
			setcookie('dsidx-visitor-auth', $response->Visitor->Auth, $remember, '/');
		}

		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function GetVisitor() {
		$post_vars = $_POST;

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("GetVisitor", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);

		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function Register(){
		
		foreach($_POST as $key => $value) {
			$post_vars[str_replace('newVisitor_', 'newVisitor.', $key)] = $_POST[$key];
		}

		if(defined('ZPRESS_API') && ZPRESS_API != ''){
			if(SNS_ARN_CONTACT_REQUEST != ''){
				$name = $post_vars['name'];
				$name_split = preg_split('/[\s]+/', $post_vars['name'], 2, PREG_SPLIT_NO_EMPTY);

				// call sns to send the contact to Zillow.com
				$sns = new AmazonSNS(array('key' => AWS_KEY, 'secret' => AWS_SECRET_KEY, 'certificate_authority' => true));
				$sns->publish(SNS_ARN_CONTACT_REQUEST, json_encode((object) array(
					'ContactDate' => gmdate('Y-m-d\TH:i:s.uP'),
					'Email' => $post_vars['newVisitor.Email'],
					'FirstName' => $post_vars['newVisitor.FirstName'],
					'LastName' => $post_vars['newVisitor.LastName'],
					'Message' => 'Registered new IDX account',
					'Phone' => $post_vars['newVisitor.PhoneNumber'],
					//'Subject' => '',
					'Zuid' => get_option('zuid'),
					'ListingUrl' => @$post_vars['newVisitor.ListingUrl'],
					'Uid' => md5(uniqid())

				)));
			}
			$post_vars["skipThirdParty"] = 'true';
		}
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Register", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		
		if($response->Success){			
			$remember = @$_POST["remember"] == "on" ? time()+60*60*24*30 : 0;
			
			setcookie('dsidx-visitor-public-id', $response->Visitor->PublicID, $remember, '/');
			setcookie('dsidx-visitor-auth', $response->Visitor->Auth, $remember, '/');
		}
		
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function UpdatePersonalInfo(){
		
		foreach($_POST as $key => $value) {
			$post_vars[str_replace('personalInfo_', 'personalInfo.', $key)] = $_POST[$key];
		}
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("UpdatePersonalInfo", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function Searches(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Searches", null, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function ToggleSearchAlert(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ToggleSearchAlert", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function DeleteSearch(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("DeleteSearch", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function FavoriteStatus(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("FavoriteStatus", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function Favorite(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Favorite", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function VisitorListings(){
				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("VisitorListings", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadAreasByType(){
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("LocationsByType", $_POST, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadSimilarListings() {
		$apiParams = array();
		$apiParams["query.SimilarToPropertyID"] = $_POST["PropertyID"];
		$apiParams["query.ListingStatuses"] = '1';
		$apiParams['responseDirective.ViewNameSuffix'] = 'Similar';
		$apiParams['responseDirective.IncludeDisclaimer'] = 'true';
		$apiParams['directive.ResultsPerPage'] = '6';

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiParams, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadSoldListings(){
		$apiParams = array();

		$apiParams["query.SimilarToPropertyID"] = $_POST["PropertyID"];
		$apiParams["query.ListingStatuses"] = '8';
		$apiParams['responseDirective.ViewNameSuffix'] = 'Sold';
		$apiParams['directive.ResultsPerPage'] = '6';

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiParams, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadSchools() {
		$apiParams = array();

		$apiParams['responseDirective.ViewNameSuffix'] = 'Schools';
		$apiParams['query.City'] = $_POST['city'];
		$apiParams['query.State'] = $_POST['state'];
		$apiParams['query.Spatial'] = $_POST['spatial'];
		$apiParams['query.PropertyID'] = $_POST['PropertyID'];

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Schools", $apiParams, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadDistricts() {
		$apiParams = array();

		$apiParams['responseDirective.ViewNameSuffix'] = 'Districts';
		$apiParams['query.City'] = $_POST['city'];
		$apiParams['query.State'] = $_POST['state'];
		$apiParams['query.Spatial'] = $_POST['spatial'];
		$apiParams['query.PropertyID'] = $_POST['PropertyID'];

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Districts", $apiParams, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function AutoComplete() {
		$apiParams = array();
		
		$apiParams['query.partialLocationTerm'] = $_GET['term'];
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('AutoCompleteOmniBox', $apiParams, false, 0);
		
		header('Content-Type: application/json');
		echo $apiHttpResponse['body'];
		die();
	}
	static function GetPhotosXML() {
		$post_vars = array_map("stripcslashes", $_GET);
		$apiRequestParams = array();
		$apiRequestParams['propertyid'] = $post_vars['pid'];
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('Photos', $apiRequestParams, false, 0);
		header('Content-type: text/xml');
		echo $apiHttpResponse['body'];
		die();
	}
}
if(!empty($_REQUEST['action']))
{
	call_user_func(array('dsSearchAgent_ClientAssist',  $_REQUEST['action']));
}
else
{
	die;
}
?>