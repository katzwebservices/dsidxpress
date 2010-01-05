<?php
class dsSearchAgent_Shortcodes {
	static function Listing($atts, $content = null, $code = "") {
		$options = get_option("dssearchagent-wordpress-edition");
			if (!$options["Activated"])
				return "";
		
		$atts = shortcode_atts(array(
			"mlsnumber"			=> "",
			"showall"			=> "false",
			"showschools"		=> "false",
			"showpricehistory"	=> "false",
			"showextradetails"	=> "false",
			"showfeatures"		=> "false",
			"showlocation"		=> "false"
		), $atts);
		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["query.MlsNumber"] = $atts["mlsnumber"];
		$apiRequestParams["responseDirective.ShowSchools"] = $atts["showschools"];
		$apiRequestParams["responseDirective.ShowPriceHistory"] = $atts["showextradetails"];
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
		if ($apiHttpResponse["response"]["code"] == "404") {
			return "<p class=\"dsidx-error\">We're sorry, but we couldn't find MLS # {$atts[mlsnumber]} in our database. This property was most likely taken off the market.</p>";
		}
		else if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
			return $apiHttpResponse["body"];
		} else {
			return "<p class=\"dsidx-error\">We're sorry, but it seems that we're having some problems loading MLS # {$atts[mlsnumber]} from our database. Please check back soon.</p>";
		}
	}
	static function Listings($atts, $content = null, $code = "") {
		$options = get_option("dssearchagent-wordpress-edition");
			if (!$options["Activated"])
				return "";
		
		$atts = shortcode_atts(array(
			"city"		=> "",
			"community"	=> "",
			"tract"		=> "",
			"zip"		=> "",
			"minprice"	=> "",
			"maxprice"	=> "",
			"linkid"	=> "",
			"count"		=> "5",
			"orderby"	=> "DateAdded",
			"orderdir"	=> "DESC"
		), $atts);
		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["responseDirective.IncludeMetadata"] = "true";
		$apiRequestParams["responseDirective.IncludeLinkMetadata"] = "true";
		$apiRequestParams["query.Cities"] = $atts["city"];
		$apiRequestParams["query.Communities"] = $atts["community"];
		$apiRequestParams["query.TractIdentifiers"] = $atts["tract"];
		$apiRequestParams["query.ZipCodes"] = $atts["zip"];
		$apiRequestParams["query.PriceMin"] = $atts["minprice"];
		$apiRequestParams["query.PriceMax"] = $atts["maxprice"];
		if ($atts["linkid"]) {
			$apiRequestParams["query.LinkID"] = $atts["linkid"];
			$apiRequestParams["query.ForceUsePropertySearchConstraints"] = "true";
		}
		$apiRequestParams["directive.ResultsPerPage"] = $atts["count"];
		$apiRequestParams["directive.SortOrders[0].Column"] = $atts["orderby"];
		$apiRequestParams["directive.SortOrders[0].Direction"] = $atts["orderdir"];
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams);
		if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
			return $apiHttpResponse["body"];
		} else {
			return "<p class=\"dsidx-error\">We're sorry, but it seems that we're having some problems loading MLS data from our database. Please check back soon.</p>";
		}
	}
}

add_shortcode("idx-listing", "dsSearchAgent_ShortCodes::Listing");
add_shortcode("idx-listings", "dsSearchAgent_ShortCodes::Listings");
?>