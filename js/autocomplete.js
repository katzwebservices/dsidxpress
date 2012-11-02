jQuery().ready(function($) {
	var cache = {};
	var omnibox = $('.dsidx-search-omnibox-autocomplete');

	if(omnibox.length > 0){
		$('.dsidx-search-omnibox-autocomplete').autocomplete({
			source: function(request, response) {
				var term = request.term;
			
				// since we no longer know what the correct search type is, revert to the default
				$(this.element).attr('name', 'idx-q-Locations<0>');
			
				// check if we've cached this autocomplete locally
				if (term in cache) {
					response(cache[term]);
					return;
				}
			
				// load autocomplete data
				var pluginUrl = (typeof dsidx != "undefined" && typeof dsidx.pluginUrl != "undefined") ? dsidx.pluginUrl : localdsidx.pluginUrl;
				$.getJSON(pluginUrl + 'client-assist.php?action=AutoComplete', request, function(data) {
					if ($.isEmptyObject(data)) {
						data = [{'Name': 'No locations, addresses, or MLS numbers found', 'Type': 'Error'}]
					}
					cache[term] = data;
					response(data);
				});
			},
			select: function(event, ui) {
				if (ui.item.Type != 'Error') {
					if (ui.item.Type == 'Listing' && ui.item.SupportingInfo.indexOf('MLS Number;') != -1) {
						// redirect MLS selection to the details page
						var idx_pos = window.location.pathname.indexOf('/idx');
						if (idx_pos > -1) {
							var path = window.location.pathname.slice(0, idx_pos + 5);
							var url  = path + 'mls-' + ui.item.Name + '-';
						} else {
							var url = localdsidx.homeUrl + '/idx/mls-' + ui.item.Name + '-';
						}
					
						window.location = url;
					} else if (ui.item.Type == 'Listing' && ui.item.SupportingInfo.indexOf('Address;') != -1) {
						$(this).attr('name', 'idx-q-AddressMasks<0>');
					} else if (ui.item.Type == 'County') {
						$(this).attr('name', 'idx-q-Counties<0>');
					} else if (ui.item.Type == 'Zip') {
						$(this).attr('name', 'idx-q-ZipCodes<0>');
					} else {
						$(this).attr('name', 'idx-q-Locations<0>');
					}
				
					$(this).val(ui.item.Name);
				}
			
				return false;
			},
			selectFirst: true
		}).data("autocomplete")._renderItem = function(ul, item) {
			var name = (item.Type == 'County') ? item.Name + ' (County)' : item.Name;
			return $('<li></li>').data('item.autocomplete', item).append('<a>' + name + '</a>').appendTo(ul);
		};
	}
	
	$('.ui-autocomplete-input').live('autocompleteopen', function() {
		var autocomplete = $(this).data('autocomplete'),
		menu = autocomplete.menu;
		
		if (!autocomplete.options.selectFirst) {
			return;
		}
		
		menu.activate($.Event({ type: 'mouseenter' }), menu.element.children().first());
	});
});
