<?php

class dsidx_footer {
	static $disclaimer_queued = false;
	static $viewName = null;

	static function ensure_disclaimer_exists($view = null) {
		if (self::$disclaimer_queued)
			return;

		add_action("wp_footer", array("dsidx_footer", "insert_disclaimer"));
		if (!empty($view)) self::$viewName = $view;
		self::$disclaimer_queued = true;
	}

	static function insert_disclaimer() {
		global $wp_query;
		
		if (is_array($wp_query->query)
		    && ((isset($wp_query->query["idx-action"]) && $wp_query->query["idx-action"] == "details")
		    || (isset($wp_query->query["idx-action"]) && $wp_query->query["idx-action"] == "results"))
		   )
			return;


		$apiParams = array();
		$apiParams["responseDirective.IncludeDsDisclaimer"] = (defined('ZPRESS_API') && ZPRESS_API != '') ? "false" : "true";
		if (!empty(self::$viewName))
			$apiParams["responseDirective.ViewName"] = self::$viewName;

		$disclaimer = dsSearchAgent_ApiRequest::FetchData("Disclaimer", $apiParams);
		echo $disclaimer["body"];
	}
}