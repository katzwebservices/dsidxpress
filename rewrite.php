<?php
add_filter("rewrite_rules_array", "dsSearchAgent_Rewrite::InsertRules");
add_filter("query_vars", "dsSearchAgent_Rewrite::SaveQueryVars");

class dsSearchAgent_Rewrite {
	private static $UrlSlug = "idx/";
	static function GetUrlSlug() {
		return self::$UrlSlug;
	}
	static function InsertRules($incomingRules) {
		$options = get_option("dssearchagent-wordpress-edition");
			if (!$options["Activated"])
				return $incomingRules;
		
		$slug = self::GetUrlSlug();
		$idxRules = array(
			$slug . "city/([^/]+)(?:/page\-(\\d+))?"		=> 'index.php?idx-action=results&idx-q-Cities=$matches[1]&idx-d-ResultPage=$matches[2]',
			$slug . "community/([^/]+)(?:/page\-(\\d+))?"	=> 'index.php?idx-action=results&idx-q-Communities=$matches[1]&idx-d-ResultPage=$matches[2]',
			$slug . "tract/([^/]+)(?:/page\-(\\d+))?"		=> 'index.php?idx-action=results&idx-q-TractIdentifiers=$matches[1]&idx-d-ResultPage=$matches[2]',
			$slug . "zip/(\\d+)(?:/page\-(\\d+))?"			=> 'index.php?idx-action=results&idx-q-ZipCodes=$matches[1]&idx-d-ResultPage=$matches[2]',
			$slug . "(\\d+)[^/]*(?:/page\-(\\d+))?"			=> 'index.php?idx-action=results&idx-q-LinkID=$matches[1]&idx-d-ResultPage=$matches[2]',
			$slug . "mls-(.+)-.*"							=> 'index.php?idx-action=details&idx-q-MlsNumber=$matches[1]',
			substr($slug, 0, strlen($slug) - 1) . "(?:/page\-(\\d+))?"	=> 'index.php?idx-action=results&idx-d-ResultPage=$matches[1]'
		);
		
		return $idxRules + $incomingRules;
	}
	static function SaveQueryVars($queryVars) {
		$queryVars[] = "idx-action";
		$queryVars[] = "idx-q-Cities";
		$queryVars[] = "idx-q-Communities";
		$queryVars[] = "idx-q-TractIdentifiers";
		$queryVars[] = "idx-q-ZipCodes";
		$queryVars[] = "idx-q-LinkID";
		$queryVars[] = "idx-q-MlsNumber";
		$queryVars[] = "idx-d-ResultPage";

		// there will be a bunch of other parameters that will be used in the final API call, but we only need to
		// be concerned with the ones in the pseudo- URL rewrite thing. the rest of the parameters will be passed
		// as HTTP GET or POST vars, so we can just use the superglobal $_REQUEST to access those  
		
		return $queryVars;
	}
}
?>