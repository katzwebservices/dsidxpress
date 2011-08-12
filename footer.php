<?php

class dsidx_footer {
	static $disclaimer_queued = false;

	static function ensure_disclaimer_exists() {
		if (self::$disclaimer_queued)
			return;

		add_action("wp_footer", array("dsidx_footer", "insert_disclaimer"));
		self::$disclaimer_queued = true;
	}

	static function insert_disclaimer() {
		global $wp_query;

		if (is_array($wp_query->query)
		    && ($wp_query->query["idx-action"] == "details" || $wp_query->query["idx-action"] == "results")
		   )
			return;

		$disclaimer = dsSearchAgent_ApiRequest::FetchData("Disclaimer");
		echo $disclaimer["body"];
	}
}