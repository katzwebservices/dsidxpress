<?php
class dsSearchAgent_ClientAssist {
	static function SlideshowXml() {
		$uriSuffix = '';
		if (array_key_exists('uriSuffix', $_GET))
			$uriSuffix = $_GET['uriSuffix'];
		
		$urlBase = $_GET['uriBase'];
		
		if (!preg_match("/^http:\/\//", $urlBase))
			$urlBase = "http://" . $urlBase;
		
		header('Content-Type: text/xml');
		echo '<?xml version="1.0"?><gallery><album lgpath="' . str_replace(array('&', '"'), array('&amp;', '&quot;'), $urlBase) . '" fspath="' . str_replace(array('&', '"'), array('&amp;', '&quot;'), $_GET['uriBase']) . '">';
		for($i = 0; $i < (int)$_GET['count']; $i++) {
			echo '<img src="' . $i . '-medium.jpg' . $uriSuffix . '" fs="' . $i . '-full.jpg' . $uriSuffix . '" />';
		}
		echo '</album></gallery>';
	}
}
call_user_func('dsSearchAgent_ClientAssist::' . $_REQUEST['action']);
?>