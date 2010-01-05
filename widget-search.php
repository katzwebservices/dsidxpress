<?php
class dsSearchAgent_SearchWidget extends WP_Widget {
	function dsSearchAgent_SearchWidget() {
		$this->WP_Widget("dsidx-search", "IDX Search", array(
			"classname" => "dsidx-widget-search",
			"description" => "A real estate search form"
		));
	}
	function widget($args, $instance) {
		extract($args);
		extract($instance);
		$title = apply_filters("widget_title", $title);
		
		$formAction = get_bloginfo("url");
		if (substr($formAction, strlen($formAction), 1) != "/")
			$formAction .= "/";
		$formAction .= dsSearchAgent_Rewrite::GetUrlSlug();
		
		$defaultSearchPanels = dsSearchAgent_ApiRequest::FetchData("AccountSearchPanelsDefault", array(), false, 60 * 60 * 24);
		$defaultSearchPanels = $defaultSearchPanels["response"]["code"] == "200" ? json_decode($defaultSearchPanels["body"]) : null;
		
		$propertyTypes = dsSearchAgent_ApiRequest::FetchData("AccountSearchSetupPropertyTypes", array(), false, 60 * 60 * 24);
		$propertyTypes = $propertyTypes["response"]["code"] == "200" ? json_decode($propertyTypes["body"]) : null;
		
		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;
		
		echo <<<HTML
			<div class="dsidx-search-widget dsidx-widget">
			<form action="{$formAction}" method="get">
				<table>
					<tr>
						<td colspan="2">
							<select name="idx-q-PropertyTypes" style="width: 100%;">
								<option value="">- All property types -</option>
HTML;
		
		foreach ($propertyTypes as $propertyType) {
			if ($propertyType->IsSearchedByDefault)
				continue;
			
			$name = htmlentities($propertyType->DisplayName);
			echo "<option value=\"{$propertyType->SearchSetupPropertyTypeID}\">{$name}</option>";
		}
		
		echo <<<HTML
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="idx-q-Cities">City</label></th>
						<td>
							<select id="idx-q-Cities" name="idx-q-Cities">
HTML;
		foreach ($searchOptions["cities"] as $city) {
			$city = htmlentities($city);
			echo "<option value=\"{$city}\">{$city}</option>";
		}
		
		echo <<<HTML
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="idx-q-PriceMin">Min price</label></th>
						<td><input id="idx-q-PriceMin" name="idx-q-PriceMin" type="text" class="dsidx-price" /></td>
					</tr>
					<tr>
						<th><label for="idx-q-PriceMax">Max price</label></th>
						<td><input id="idx-q-PriceMax" name="idx-q-PriceMax" type="text" class="dsidx-price" /></td>
					</tr>
HTML;
		foreach ($defaultSearchPanels as $key => $value) {
			if ($value->DomIdentifier == "search-input-home-size") {
				echo <<<HTML
					<tr>
						<th><label for="idx-q-ImprovedSqFtMin">Size</label></th>
						<td><input id="idx-q-ImprovedSqFtMin" name="idx-q-ImprovedSqFtMin" type="text" class="dsidx-improvedsqft" />+ sq ft</td>
					</tr>
HTML;
				break;
			}
		}
		echo <<<HTML
					<tr>
						<th><label for="idx-q-BedsMin">Beds</label></th>
						<td><input id="idx-q-BedsMin" name="idx-q-BedsMin" type="text" class="dsidx-beds" />+</td>
					</tr>
					<tr>
						<th><label for="idx-q-BathsMin">Baths</label></th>
						<td><input id="idx-q-BathsMin" name="idx-q-BathsMin" type="text" class="dsidx-baths" />+</td>
					</tr>
				</table>
				<div class="dsidx-search-button">
					<input type="submit" value="Search for properties" />
				</div>
			</form>
			</div>
HTML;
		echo $after_widget;
	}
	function update($new_instance, $old_instance) {
		$new_instance["title"] = strip_tags($new_instance["title"]);
		$new_instance["searchOptions"]["cities"] = explode("\n", $new_instance["searchOptions"]["cities"]);
		
		if ($new_instance["searchOptions"]["sortCities"])
			sort($new_instance["searchOptions"]["cities"]);
		
		// we don't need to store this option
		unset($new_instance["searchOptions"]["sortCities"]);
		
		foreach ($new_instance["searchOptions"]["sortCities"] as &$area)
			$area = str_replace("\r", "", $area);
		
		return $new_instance;
	}
	function form($instance) {
		$instance = wp_parse_args($instance, array(
			"title" => "Real Estate Search",
			"searchOptions" => array(
				"cities" => array()
			)
		));

		$title = htmlspecialchars($instance["title"]);
		$cities = htmlspecialchars(implode("\n", (array)$instance["searchOptions"]["cities"]));
		
		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$searchOptionsFieldId = $this->get_field_id("searchOptions");
		$searchOptionsFieldName = $this->get_field_name("searchOptions");
		
		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>
			
			<p>
				<label for="{$searchOptionsFieldId}[cities]">Cities (one per line)</label>
				<textarea id="{$searchOptionsFieldId}[cities]" name="{$searchOptionsFieldName}[cities]" class="widefat" rows="10">{$cities}</textarea>
			</p>
			
			<p>
				<label for="{$searchOptionsFieldId}[sortCities]">Sort cities?</label>
				<input id="{$searchOptionsFieldId}[sortCities]" name="{$searchOptionsFieldName}[sortCities]" class="checkbox" type="checkbox" />
			</p>
HTML;
	}
}
?>