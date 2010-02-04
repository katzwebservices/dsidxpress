<?php
class dsSearchAgent_ListAreasWidget extends WP_Widget {
	function dsSearchAgent_ListAreasWidget() {
		$this->WP_Widget("dsidx-list-areas", "IDX Areas", array(
			"classname" => "dsidx-widget-list-areas",
			"description" => "Lists of links for showing real estate"
		));

		if (is_admin())
			wp_enqueue_script('dsidxpress_widget_list_areas', DSIDXPRESS_PLUGIN_URL . 'js/widget-list-areas.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION);
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
			$area = htmlentities($area);
			$areaType = $areaOptions[areaType];
			$area_pair = preg_split('/\|/', $area, -1);
			$area_title = count($area_pair) == 2 ? $area_pair[0] : $area;
			$areaUrl = count($area_pair) == 2 ?
				urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $area_pair[1]))) :
				urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $area)));

			echo "<li><a href=\"{$urlBase}{$areaType}/{$areaUrl}/\">{$area_title}</a></li>";
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
			$area = trim($area);

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

		$advancedId = $this->get_field_id("advanced");

		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$areaOptionsFieldId = $this->get_field_id("areaOptions");
		$areaOptionsFieldName = $this->get_field_name("areaOptions");
		$selectedAreaType = array($instance["areaOptions"]["areaType"] => "selected=\"selected\"");
		$type_normalized = ucwords($instance["areaOptions"]["areaType"]);
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;

		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>

			<p>
				<label for="{$areaOptionsFieldId}[areaType]">Area types</label>
				<select class="widefat" id="{$areaOptionsFieldId}_areaType" name="{$areaOptionsFieldName}[areaType]" onchange="dsWidgetListAreas.SwitchType(this, '{$areaOptionsFieldId}_link_title')">
					<option value="city" {$selectedAreaType[city]}>Cities</option>
					<option value="community" {$selectedAreaType[community]}>Communities</option>
					<option value="tract" {$selectedAreaType[tract]}>Tracts</option>
					<option value="zip" {$selectedAreaType[zip]}>Zip Codes</option>
				</select>
			</p>

			<h3>Areas (one per line)</h3>
			<p>
				<textarea id="{$areaOptionsFieldId}_areas" name="{$areaOptionsFieldName}[areas]" class="widefat" rows="10">{$areas}</textarea>
			</p>

			<p>
				<label for="{$areaOptionsFieldId}[sortAreas]">Sort areas?</label>
				<input id="{$areaOptionsFieldId}_sortAreas" name="{$areaOptionsFieldName}[sortAreas]" class="checkbox" type="checkbox" />
			</p>
			<a href="javascript:void(0);" onclick="jQuery('#{$advancedId}_advanced').slideDown(500); jQuery(this).hide()">Advanced</a>
			<div id="{$advancedId}_advanced" style="display:none">
				<hr />
				<h3>Add an Area w/ a Custom Title</h3>
				<p>
					<label for="{$advancedId}_title">Link Text</label>
					<input id="{$advancedId}_title" value="" class="widefat" type="text" />
				</p>
				<p>
					<label for="{$advancedId}_lookup">Actual Area Name</label>
					<input id="{$advancedId}_lookup" value="" class="widefat" type="text" />
					<span class="description">See all <span id="{$areaOptionsFieldId}_link_title">{$type_normalized}</span> Names <a href="javascript:void(0);" onclick="dsWidgetListAreas.LaunchLookupList('{$pluginUrl}locations.php', '{$areaOptionsFieldId}_areaType')">here</a></span>
				</p>

				<input type="button" class="button" value="Add This Area" onclick="dsWidgetListAreas.AddArea('{$advancedId}_title', '{$advancedId}_lookup', '{$areaOptionsFieldId}_areas')"/>
			</div>
HTML;
	}
}
?>