<?php
class dsSearchAgent_Shortcodes {
	static function Listing($atts, $content = null, $code = "") {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
			if (!$options["Activated"])
				return "";

		$atts = shortcode_atts(array(
			"mlsnumber"			=> "",
			"showall"			=> "false",
			"showpricehistory"	=> "false",
			"showschools"		=> "false",
			"showextradetails"	=> "false",
			"showfeatures"		=> "false",
			"showlocation"		=> "false"
		), $atts);
		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["query.MlsNumber"] = str_replace(" ", "", $atts["mlsnumber"]);
		$apiRequestParams["responseDirective.ShowSchools"] = $atts["showschools"];
		$apiRequestParams["responseDirective.ShowPriceHistory"] = $atts["showpricehistory"];
		$apiRequestParams["responseDirective.ShowAdditionalDetails"] = $atts["showextradetails"];
		$apiRequestParams["responseDirective.ShowFeatures"] = $atts["showfeatures"];
		$apiRequestParams["responseDirective.ShowLocation"] = $atts["showlocation"];

		if ($atts["showall"] == "true") {
			$apiRequestParams["responseDirective.ShowSchools"] = "true";
			$apiRequestParams["responseDirective.ShowPriceHistory"] = "true";
			$apiRequestParams["responseDirective.ShowAdditionalDetails"] = "true";
			$apiRequestParams["responseDirective.ShowFeatures"] = "true";
			$apiRequestParams["responseDirective.ShowLocation"] = "true";
		}

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiRequestParams);
		add_action("wp_footer", array("dsSearchAgent_Shortcodes", "InsertDisclaimer"));

		if ($apiHttpResponse["response"]["code"] == "404") {
			return "<p class=\"dsidx-error\">We're sorry, but we couldn't find MLS # {$atts[mlsnumber]} in our database. This property may be a new listing or possibly taken off the market. Please check back again.</p>";
		}
		else if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
			return $apiHttpResponse["body"];
		} else {
			return "<p class=\"dsidx-error\">We're sorry, but it seems that we're having some problems loading MLS # {$atts[mlsnumber]} from our database. Please check back soon.</p>";
		}
	}
	static function Listings($atts, $content = null, $code = "") {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
			if (!$options["Activated"])
				return "";

		$atts = shortcode_atts(array(
			"city"			=> "",
			"community"		=> "",
			"tract"			=> "",
			"zip"			=> "",
			"minprice"		=> "",
			"maxprice"		=> "",
			"propertytypes"	=> "",
			"linkid"		=> "",
			"count"			=> "5",
			"orderby"		=> "DateAdded",
			"orderdir"		=> "DESC",
			"showlargerphotos"	=> "false"
		), $atts);
		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["responseDirective.IncludeMetadata"] = "true";
		$apiRequestParams["responseDirective.IncludeLinkMetadata"] = "true";
		$apiRequestParams["responseDirective.ShowLargerPhotos"] = $atts["showlargerphotos"];
		$apiRequestParams["query.Cities"] = htmlspecialchars_decode($atts["city"]);
		$apiRequestParams["query.Communities"] = htmlspecialchars_decode($atts["community"]);
		$apiRequestParams["query.TractIdentifiers"] = htmlspecialchars_decode($atts["tract"]);
		$apiRequestParams["query.ZipCodes"] = $atts["zip"];
		$apiRequestParams["query.PriceMin"] = $atts["minprice"];
		$apiRequestParams["query.PriceMax"] = $atts["maxprice"];
		if ($atts["propertytypes"]) {
			$propertyTypes = explode(",", str_replace(" ", "", $atts["propertytypes"]));
			$propertyTypes = array_combine(range(0, count($propertyTypes) - 1), $propertyTypes);
			foreach ($propertyTypes as $key => $value)
				$apiRequestParams["query.PropertyTypes[{$key}]"] = $value;
		}
		if ($atts["linkid"]) {
			$apiRequestParams["query.LinkID"] = $atts["linkid"];
			$apiRequestParams["query.ForceUsePropertySearchConstraints"] = "true";
		}
		$apiRequestParams["directive.ResultsPerPage"] = $atts["count"];
		$apiRequestParams["directive.SortOrders[0].Column"] = $atts["orderby"];
		$apiRequestParams["directive.SortOrders[0].Direction"] = $atts["orderdir"];

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams);
		add_action("wp_footer", array("dsSearchAgent_Shortcodes", "InsertDisclaimer"));

		if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
			return $apiHttpResponse["body"];
		} else {
			return "<p class=\"dsidx-error\">We're sorry, but it seems that we're having some problems loading MLS data from our database. Please check back soon.</p>";
		}
	}
	static function InsertDisclaimer() {
		$disclaimer = dsSearchAgent_ApiRequest::FetchData("Disclaimer");
		echo $disclaimer["body"];
	}
}

add_shortcode("idx-listing", array("dsSearchAgent_ShortCodes", "Listing"));
add_shortcode("idx-listings", array("dsSearchAgent_ShortCodes", "Listings"));
?>