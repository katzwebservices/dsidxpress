<?php
class dsSearchAgent_ListingsWidget extends WP_Widget {
	function dsSearchAgent_ListingsWidget() {
		$this->WP_Widget("dsidx-listings", "IDX Listings", array(
			"classname" => "dsidx-widget-listings",
			"description" => "Show a list of real estate listings"
		));
	}
	function widget($args, $instance) {
		extract($args);
		extract($instance);
		$title = apply_filters("widget_title", $title);
		
		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;
		
		$apiRequestParams = array();
		$apiRequestParams["directive.ResultsPerPage"] = $listingsToShow;
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "widget";
		$apiRequestParams["responseDirective.DefaultDisplayType"] = $defaultDisplay;
		
		if ($querySource == "area") {
			$sort = explode("|", $areaSourceConfig["sort"]);
			switch ($areaSourceConfig["type"]) {
				case "city":
					$typeKey = "query.Cities";
					break;
				case "community":
					$typeKey = "query.Communities";
					break;
				case "tract":
					$typeKey = "query.TractIdentifiers";
					break;
				case "zip":
					$typeKey = "query.ZipCodes";
					break;
			}
			$apiRequestParams[$typeKey] = $areaSourceConfig["name"];
			$apiRequestParams["directive.SortOrders[0].Column"] = $sort[0];
			$apiRequestParams["directive.SortOrders[0].Direction"] = $sort[1];
		} else if ($querySource == "link") {
			$apiRequestParams["query.ForceUsePropertySearchConstraints"] = "true";
			$apiRequestParams["query.LinkID"] = $linkSourceConfig["linkId"];
		} else if ($querySource == "agentlistings") {
			$apiRequestParams["responseDirective.OnlyAgentsListings"] = "true";
			$apiRequestParams["directive.SortOrders[0].Column"] = "DateAdded";
			$apiRequestParams["directive.SortOrders[0].Direction"] = "DESC";
		} else if ($querySource == "officelistings") {
			$apiRequestParams["responseDirective.OnlyOfficesListings"] = "true";
			$apiRequestParams["directive.SortOrders[0].Column"] = "DateAdded";
			$apiRequestParams["directive.SortOrders[0].Direction"] = "DESC";
		}
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams);
		if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
			$data = $apiHttpResponse["body"];
		} else {
			$data = "<p class=\"dsidx-error\">We're sorry, but it seems that we're having some problems loading properties from our database. Please check back soon.</p>";
		}
		
		$data = str_replace('{$pluginUrlPath}', $dsSearchAgent_PluginUrl, $data);
		
		echo $data;
		echo $after_widget;
	}
	function update($new_instance, $old_instance) {
		// we need to do this first-line awkwardness so that the title comes through in the sidebar display thing
		$new_instance["listingsOptions"]["title"] = $new_instance["title"];
		$new_instance = $new_instance["listingsOptions"];
		return $new_instance;
	}
	function form($instance) {
		$instance = wp_parse_args($instance, array(
			"title"				=> "Latest Real Estate",
			"listingsToShow"	=> "25",
			"defaultDisplay"	=> "listed",
			"querySource"		=> "area",
			"areaSourceConfig"	=> array(
				"type"			=> "city",
				"name"			=> "",
				"sort"			=> "DateAdded|DESC"
			),
			"linkSourceConfig"	=> array(
				"linkId"		=> ""
			)
		));
		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$baseFieldId = $this->get_field_id("listingsOptions");
		$baseFieldName = $this->get_field_name("listingsOptions");
		
		$checkedDefaultDisplay = array($instance["defaultDisplay"] => "checked=\"checked\"");
		$checkedQuerySource = array($instance["querySource"] => "checked=\"checked\"");
		$selectedAreaType = array($instance["areaSourceConfig"]["type"] => "selected=\"selected\"");
		$selectedSortOrder = array(str_replace("|", "", $instance["areaSourceConfig"]["sort"]) => "selected=\"selected\"");
		$selectedLink = array($instance["linkSourceConfig"]["linkId"] => "selected=\"selected\"");
		
		$availableLinks = dsSearchAgent_ApiRequest::FetchData("AccountAvailableLinks", array(), true, 0);
		$availableLinks = json_decode($availableLinks["body"]);
		
		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$instance[title]}" class="widefat" type="text" />
			</p>
			<p>
				<label for="{$baseFieldId}[listingsToShow]"># of listings to show (max 50)</label>
				<input id="{$baseFieldId}[listingsToShow]" name="{$baseFieldName}[listingsToShow]" value="{$instance[listingsToShow]}" class="widefat" type="text" />
			</p>
			<p>
				<input type="radio" name="{$baseFieldName}[defaultDisplay]" id="{$baseFieldId}[defaultDisplay-listed]" value="listed" {$checkedDefaultDisplay[listed]}/>
				<label for="{$baseFieldId}[defaultDisplay-listed]">Show in list by default</label>
				<br />
				<input type="radio" name="{$baseFieldName}[defaultDisplay]" id="{$baseFieldId}[defaultDisplay-slideshow]" value="slideshow" {$checkedDefaultDisplay[slideshow]}/>
				<label for="{$baseFieldId}[defaultDisplay-slideshow]">Show details by default</label>
				<br />
				<input type="radio" name="{$baseFieldName}[defaultDisplay]" id="{$baseFieldId}[defaultDisplay-map]" value="map" {$checkedDefaultDisplay[map]}/>
				<label for="{$baseFieldId}[defaultDisplay-map]">Show on map by default</label>
			</p>
			
			<div class="widefat" style="border-width: 0 0 1px; margin: 20px 0;"></div>
			
			<table>
				<tr>
					<td style="width: 20px;"><p><input type="radio" name="{$baseFieldName}[querySource]" id="{$baseFieldId}[querySource-area]" value="area" {$checkedQuerySource[area]}/></p></td>
					<td><p><label for="{$baseFieldId}[querySource-area]">Pick an area</label></p></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<p>
							<label for="{$baseFieldId}[areaSourceConfig][type]">Area type</label>
							<select id="{$baseFieldId}[areaSourceConfig][type]" name="{$baseFieldName}[areaSourceConfig][type]" class="widefat">
								<option value="city" {$selectedAreaType[city]}>City</option>
								<option value="community" {$selectedAreaType[community]}>Community</option>
								<option value="tract" {$selectedAreaType[tract]}>Tract</option>
								<option value="zip" {$selectedAreaType[zip]}>Zip Code</option>
							</select>
						</p>
						
						<p>
							<label for="{$baseFieldId}[areaSourceConfig][name]">Area name</label>
							<input id="{$baseFieldId}[areaSourceConfig][name]" name="{$baseFieldName}[areaSourceConfig][name]" class="widefat" type="text" value="{$instance[areaSourceConfig][name]}" />
						</p>
						
						<p>
							<label for="{$baseFieldId}[areaSourceConfig][sort]">Sort order</label>
							<select id="{$baseFieldId}[areaSourceConfig][sort]" name="{$baseFieldName}[areaSourceConfig][sort]" class="widefat">
								<option value="DateAdded|DESC" {$selectedSortOrder[DateAddedDESC]}>Time on market, newest first</option>
								<option value="Price|DESC" {$selectedSortOrder[PriceDESC]}>Price, highest first</option>
								<option value="OverallPriceDropPercent|DESC" {$selectedSortOrder[OverallPriceDropPercentDESC]}>Price drop %, largest first</option>
								<option value="WalkScore|DESC" {$selectedSortOrder[WalkScoreDESC]}>Walk Score&trade;, highest first</option>
								<option value="ImprovedSqFt|DESC" {$selectedSortOrder[ImprovedSqFtDESC]}>Improved size, largest first</option>
								<option value="LotSqFt|DESC" {$selectedSortOrder[LotSqFtDESC]}>Lot size, largest first</option>
							</select>
						</p>
					</td>
				</tr>
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td><p><input type="radio" name="{$baseFieldName}[querySource]" id="{$baseFieldId}[querySource-agentlistings]" value="agentlistings" {$checkedQuerySource[agentlistings]}/></p></td>
					<td><p><label for="{$baseFieldId}[querySource-agentlistings]">My own listings (via agent ID, newest listings first)</label></p></td>
				</tr>
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td><p><input type="radio" name="{$baseFieldName}[querySource]" id="{$baseFieldId}[querySource-officelistings]" value="officelistings" {$checkedQuerySource[officelistings]}/></p></td>
					<td><p><label for="{$baseFieldId}[querySource-officelistings]">My office's listings (via office ID, newest listings first)</label></p></td>
				</tr>
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td><p><input type="radio" name="{$baseFieldName}[querySource]" id="{$baseFieldId}[querySource-link]" value="link" {$checkedQuerySource[link]}/></p></td>
					<td><p><label for="{$baseFieldId}[querySource-link]">Use a link you created in your Diverse Solutions control panel</label></p></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<p>
							<select name="{$baseFieldName}[linkSourceConfig][linkId]" class="widefat">
HTML;
		foreach ($availableLinks as $link) {
			echo "<option value=\"{$link->LinkID}\" {$selectedLink[$link->LinkID]}>{$link->Title}</option>";
		}
		echo <<<HTML
							</select>
						</p>
					</td>
				</tr>
			</table>
HTML;
	}
}
?>