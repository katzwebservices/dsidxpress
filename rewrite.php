<?php
add_filter("rewrite_rules_array", array("dsSearchAgent_Rewrite", "InsertRules"));
add_filter("query_vars", array("dsSearchAgent_Rewrite", "SaveQueryVars"));

class dsSearchAgent_Rewrite {
	static function GetUrlSlug() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		return !empty($options["UseAlternateUrlStructure"]) ? "" : "idx/";
	}
	static function InsertRules($incomingRules) {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		$idxRules = array(
			"idx/city/([^/]+)(?:/page\-(\\d+))?"       => 'index.php?idx-action=results&idx-q-Cities=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/community/([^/]+)(?:/page\-(\\d+))?"  => 'index.php?idx-action=results&idx-q-Communities=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/tract/([^/]+)(?:/page\-(\\d+))?"      => 'index.php?idx-action=results&idx-q-TractIdentifiers=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/area/([^/]+)(?:/page\-(\\d+))?"       => 'index.php?idx-action=results&idx-q-Areas=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/zip/(\\d+)(?:/page\-(\\d+))?"         => 'index.php?idx-action=results&idx-q-ZipCodes=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/(\\d+)[^/]*(?:/page\-(\\d+))?"        => 'index.php?idx-action=results&idx-q-LinkID=$matches[1]&idx-d-ResultPage=$matches[2]',
			"idx/mls-(.+)-.*"                          => 'index.php?idx-action=details&idx-q-MlsNumber=$matches[1]',
			"idx/(\\d+)-mls-(.+)-.*"                   => 'index.php?idx-action=details&idx-q-PropertyID=$matches[1]&idx-q-MlsNumber=$matches[2]',
			"idx/advanced.*"                           => 'index.php?idx-action=framed',
			"idx(?:/page\-(\\d+))?"                    => 'index.php?idx-action=results&idx-d-ResultPage=$matches[1]'
		);

		if (!empty($options["UseAlternateUrlStructure"])) {
			$stateAbbreviations = self::GetStateAbbreviations();

			foreach ($stateAbbreviations as $state) {
				$idxRules["{$state}/[^/]+/(\\d+)-mls-(.+)-.*"] =
					'index.php?idx-action=details&idx-q-PropertyID=$matches[1]&idx-q-MlsNumber=$matches[2]';
				$idxRules["({$state})/([^/]+)(?:/page\-(\\d+))?"] =
					'index.php?idx-action=results&idx-q-States=$matches[1]&idx-q-Cities=$matches[2]&idx-d-ResultPage=$matches[3]';
				$idxRules["({$state})/([^/]+)/community/([^/]+)(?:/page\-(\\d+))?"] =
					'index.php?idx-action=results&idx-q-States=$matches[1]&idx-q-Communities=$matches[2]&idx-d-ResultPage=$matches[3]';
				$idxRules["({$state})/([^/]+)/tract/([^/]+)(?:/page\-(\\d+))?"] =
					'index.php?idx-action=results&idx-q-States=$matches[1]&idx-q-TractIdentifiers=$matches[2]&idx-d-ResultPage=$matches[3]';
				$idxRules["({$state})/(\\d+)(?:/page\-(\\d+))?"] =
					'index.php?idx-action=results&idx-q-States=$matches[1]&idx-q-ZipCodes=$matches[2]&idx-d-ResultPage=$matches[3]';
			}
		}

		return $idxRules + $incomingRules;
	}
	static function SaveQueryVars($queryVars) {
		$queryVars[] = "idx-action";
		$queryVars[] = "idx-q-Cities";
		$queryVars[] = "idx-q-Communities";
		$queryVars[] = "idx-q-TractIdentifiers";
		$queryVars[] = "idx-q-Areas";
		$queryVars[] = "idx-q-ZipCodes";
		$queryVars[] = "idx-q-States";
		$queryVars[] = "idx-q-LinkID";
		$queryVars[] = "idx-q-MlsNumber";
		$queryVars[] = "idx-q-PropertyID";
		$queryVars[] = "idx-d-ResultPage";

		// there will be a bunch of other parameters that will be used in the final API call, but we only need to
		// be concerned with the ones in the pseudo- URL rewrite thing. the rest of the parameters will be passed
		// as HTTP GET or POST vars, so we can just use the superglobal $_REQUEST to access those

		return $queryVars;
	}
	static function GetStateAbbreviations() {
		return array(
			//united states
			'al',
			'ak',
			'az',
			'ar',
			'ca',
			'co',
			'ct',
			'de',
			'dc',
			'fl',
			'ga',
			'hi',
			'id',
			'il',
			'in',
			'ia',
			'ks',
			'ky',
			'la',
			'me',
			'md',
			'ma',
			'mi',
			'mn',
			'ms',
			'mo',
			'mt',
			'ne',
			'nv',
			'nh',
			'nj',
			'nm',
			'ny',
			'nc',
			'nd',
			'oh',
			'ok',
			'or',
			'pa',
			'ri',
			'sc',
			'sd',
			'tn',
			'tx',
			'ut',
			'vt',
			'va',
			'wa',
			'wv',
			'wi',
			'wy',
			// canada
			'ab',
			'bc',
			'mb',
			'nb',
			'nl',
			'ns',
			'nt',
			'nu',
			'on',
			'pe',
			'qc',
			'sk',
			'yt'
		);
	}
}
?>