
var dsidxMultiListings = (function() {
	var nodeEditing;
	var returnObj;
	
	returnObj = {
		init: function() {
			var startNode = tinyMCEPopup.editor.selection.getStart();
			var nodeTextContent = startNode.textContent; 
			var showAllIsSet;
			
			if (/^\[idx-listings /.test(nodeTextContent) && startNode.tagName == 'P') {
				nodeEditing = startNode;
				tinyMCEPopup.editor.execCommand('mceSelectNode', false, nodeEditing);
			}
			
			$('#area-type').change(this.loadAreasByType);
			this.loadAreasByType();
		},
		loadAreasByType: function() {
			$.ajax({
				url: ApiRequest.uriBase + 'LocationsByType',
				dataType: 'jsonp',
				cache: true,
				jsonpCallback: 'loadByType',
				data: {
					searchSetupID: ApiRequest.searchSetupID,
					type: $('#area-type').val(),
					minListingCount: 5
				},
				success: function(data) { 
					var options = [];
					var areaName, listingCount, urlEscapedAreaName, printableAreaName;
					
					for (var i = 0, j = data.length; i < j; ++i) {
						areaName = data[i].Name;
						listingCount = data[i].ListingCount;
						urlEscapedAreaName = escape(areaName);
						
						if (areaName.length > 20)
							printableAreaName = $('<div/>').text(areaName.substr(0, 20) + '... (' + String(listingCount) + ')').html();
						else
							printableAreaName = $('<div/>').text(areaName + ' (' + String(listingCount) + ')').html();
						options.push('<option value="' + urlEscapedAreaName + '">' + printableAreaName + '</option>');
					}
					$('#area-name').html(options.join(''));
				}
			});
		},
		changeTab: function(type) {
			if (type == 'quick-search') {
				mcTabs.displayTab('custom_search_tab', 'custom_search_panel');
			} else if (type == 'pre-saved-links') {
				mcTabs.displayTab('saved_links_tab', 'saved_links_panel');
			}
		},
		insert: function() {
			var mlsNumber = $('#mls-number').val();
			
			if (!mlsNumber)
				tinyMCEPopup.close();
			
			var shortcode = '<p>[idx-listing mlsnumber="' + mlsNumber + '"';
			
			if ($('#show-all:checked').length) {
				shortcode += ' showall="true"';
			} else {
				$('#data-show-options input:checked').each(function() {
					shortcode += ' ' + this.name + '="true"';
				});
			}
			shortcode += ']</p>';
			
			tinyMCEPopup.editor.execCommand(nodeEditing ? 'mceReplaceContent' : 'mceInsertContent', false, shortcode);
			tinyMCEPopup.close();
		},
	};
	
	return returnObj;
})();

tinyMCEPopup.onInit.add(dsidxMultiListings.init, dsidxMultiListings);
