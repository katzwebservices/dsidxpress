dsIDXPressOptions = {
	UrlBase : '',
	OptionPrefix : '',
	EnableDragDrop: false,
	
	Init : function(){
		if(dsIDXPressOptions.EnableDragDrop){
			jQuery("#dsidxpress-SitemapLocations").sortable({
				stop: function(event, ui) { dsIDXPressOptions.RepairOrder(); }
			});
			jQuery("#dsidxpress-SitemapLocations").disableSelection();
		}
	},
	
	AddSitemapLocation : function(){
		var location_name = jQuery('#dsidxpress-NewSitemapLocation').val(),
			location_type = jQuery('#dsidxpress-NewSitemapLocationType').val(),
			location_sanitized = encodeURIComponent(location_name.replace('-', '_').replace(' ', '-').toLowerCase());
			index = jQuery('#dsidxpress-SitemapLocations').children().length;
			
		var city_selected = '', community_selected = '', tract_selected = '', zip_selected = '';
		switch(location_type){
			case 'city': city_selected = ' selected="selected"'; break;
			case 'community': community_selected = ' selected="selected"'; break;
			case 'tract': tract_selected = ' selected="selected"'; break;
			case 'zip': zip_selected = ' selected="selected"'; break;
		}
		
		jQuery('#dsidxpress-NewSitemapLocation').val('');
		jQuery('#dsidxpress-SitemapLocations').append(
			(dsIDXPressOptions.EnableDragDrop ? '<li class="ui-state-default dsidxpress-SitemapLocation">' : '') +
			'<div class="arrow"><span class="dsidxpress-up_down"></span></div>' +
			'<div class="value">' +
				'<a href="'+ dsIDXPressOptions.UrlBase + location_type +'/' + location_sanitized + '" target="_blank">' + location_name + '</a>'+
				'<input type="hidden" name="'+ dsIDXPressOptions.OptionPrefix +'[SitemapLocations]['+index+'][value]" value="'+ location_name +'" />'+
			'</div>' +
			'<div class="type"><select id="dsidxpress-NewSitemapLocationType" name="'+ dsIDXPressOptions.OptionPrefix +'[SitemapLocations]['+index+'][type]">'+
				'<option value="city"'+ city_selected +'>City</option>' +
				'<option value="community"'+ community_selected +'>Community</option>' +
				'<option value="tract"'+ tract_selected +'>Tract</option>' +
				'<option value="zip"'+ zip_selected +'>Zip Code</option>' +
			'</select></div>' +
			'<div class="action"><input type="button" value="Remove" class="button" onclick="dsIDXPressOptions.RemoveSitemapLocation(this)" /></div>'+
			'<div style="clear:both"></div>'+
			'</li>');
		
		dsIDXPressOptions.RepairOrder();
	},
	
	RepairOrder : function(){
		var location_index = 0;
		jQuery('#dsidxpress-SitemapLocations').children().each(function(i){
			var location = jQuery(this);
			var value = location.find('input');
			var type = location.find('select');
			
			value.attr('name', value.attr('name').replace(/\[\d+\]/, '[' + location_index + ']'));
			type.attr('name', type.attr('name').replace(/\[\d+\]/, '[' + location_index + ']'));
			
			location_index++;
		});
	},
	
	RemoveSitemapLocation : function(button){
		if(confirm("Are you sure you want to remove this item")) {
			jQuery(button.parentNode.parentNode).remove();
			dsIDXPressOptions.RepairOrder();
		}
	}
}

jQuery(document).ready(function () {
	dsIDXPressOptions.Init();
});