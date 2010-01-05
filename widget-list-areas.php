<?php
class dsSearchAgent_ListAreasWidget extends WP_Widget {
	function dsSearchAgent_ListAreasWidget() {
		$this->WP_Widget("dsidx-list-areas", "IDX Areas", array(
			"classname" => "dsidx-widget-list-areas",
			"description" => "Lists of links for showing real estate"
		));
	}
	function widget($args, $instance) {
		extract($args);
		extract($instance);
		$title = apply_filters("widget_title", $title);
		
		$urlBase = get_bloginfo("url");
		if (substr($urlBase, strlen($urlBase), 1) != "/")
			$urlBase .= "/";
		$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug();
		
		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;
		
		echo "<ul class=\"dsidx-widget\">";
		foreach ($areaOptions["areas"] as $area) {
			$areaUrl = urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $area)));
			$area = htmlentities($area);
			$areaType = $areaOptions[areaType];
			
			echo "<li><a href=\"{$urlBase}{$areaType}/{$areaUrl}/\">{$area}</a></li>";
		}
		echo "</ul>";
		echo $after_widget;
	}
	function update($new_instance, $old_instance) {
		$new_instance["title"] = strip_tags($new_instance["title"]);
		$new_instance["areaOptions"]["areas"] = explode("\n", $new_instance["areaOptions"]["areas"]);
		
		if ($new_instance["areaOptions"]["sortAreas"])
			sort($new_instance["areaOptions"]["areas"]);
		
		// we don't need to store this option
		unset($new_instance["areaOptions"]["sortAreas"]);
		
		foreach ($new_instance["areaOptions"]["areas"] as &$area)
			$area = str_replace("\r", "", $area);
		
		return $new_instance;
	}
	function form($instance) {
		$instance = wp_parse_args($instance, array(
			"title" => "Our Coverage Areas",
			"areaOptions" => array(
				"areas" => array(),
				"areaType" => "city"
			)
		));
		
		$title = htmlspecialchars($instance["title"]);
		$areas = htmlspecialchars(implode("\n", (array)$instance["areaOptions"]["areas"]));
		
		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$areaOptionsFieldId = $this->get_field_id("areaOptions");
		$areaOptionsFieldName = $this->get_field_name("areaOptions");
		$selectedAreaType = array($instance["areaOptions"]["areaType"] => "selected=\"selected\"");
		
		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>
			
			<p>
				<label for="{$areaOptionsFieldId}[areaType]">Area types</label>
				<select class="widefat" id="{$areaOptionsFieldId}[areaType]" name="{$areaOptionsFieldName}[areaType]">
					<option value="city" {$selectedAreaType[city]}>Cities</option>
					<option value="community" {$selectedAreaType[community]}>Communities</option>
					<option value="tract" {$selectedAreaType[tract]}>Tracts</option>
					<option value="zip" {$selectedAreaType[zip]}>Zip Codes</option>
				</select>
			</p>
			
			<p>
				<label for="{$areaOptionsFieldId}[areas]">Areas (one per line)</label>
				<textarea id="{$areaOptionsFieldId}[areas]" name="{$areaOptionsFieldName}[areas]" class="widefat" rows="10">{$areas}</textarea>
			</p>
			
			<p>
				<label for="{$areaOptionsFieldId}[sortAreas]">Sort areas?</label>
				<input id="{$areaOptionsFieldId}[sortAreas]" name="{$areaOptionsFieldName}[sortAreas]" class="checkbox" type="checkbox" />
			</p>
HTML;
	}
}
?>