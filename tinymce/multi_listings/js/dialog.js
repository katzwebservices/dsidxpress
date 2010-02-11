
var dsidxMultiListings = (function() {
	var nodeEditing;
	var returnObj;
	var multiListingType = 'quick-search';
	
	returnObj = {
		init: function() {
			var startNode = tinyMCEPopup.editor.selection.getStart();
			var nodeTextContent = startNode.textContent || startNode.innerText;
			var linkId, area, minPrice, maxPrice, checkedPropertyTypes, sortColumn, sortDirection, count;
			
			if (/^\[idx-listings /.test(nodeTextContent) && startNode.tagName == 'P') {
				nodeEditing = startNode;
				tinyMCEPopup.editor.execCommand('mceSelectNode', false, nodeEditing);
				
				linkId = /^[^\]]+ linkid=['"]?(\d+)/.exec(nodeTextContent);
				
				if (linkId) {
					$('#saved-link').val(linkId[1]);
				} else {
					area = /^[^\]]+ (city|community|tract|zip)=['"]([^'"]+)/.exec(nodeTextContent);
					minPrice = /^[^\]]+ minprice=['"]?(\d+)/.exec(nodeTextContent);
					maxPrice = /^[^\]]+ maxprice=['"]?(\d+)/.exec(nodeTextContent);
					checkedPropertyTypes = /^[^\]]+ propertytypes=['"]?([\d,]+)/.exec(nodeTextContent);
					sortColumn = /^[^\]]+ orderby=['"]?([^'" ]+)/.exec(nodeTextContent);
					sortDirection = /^[^\]]+ orderdir=['"]?([^'" ]+)/.exec(nodeTextContent);
					count = /^[^\]]+ count=['"]?(\d+)/.exec(nodeTextContent);
					
					if (area)
						$('#area-type').val(area[1]);
					if (minPrice)
						$('#min-price').val(minPrice[1]);
					if (maxPrice)
						$('#max-price').val(maxPrice[1]);
					if (checkedPropertyTypes) {
						checkedPropertyTypes = checkedPropertyTypes[1].split(',');
						for (var i = 0, l = checkedPropertyTypes.length; i < l; ++i)
							$('#property-type-' + checkedPropertyTypes[i]).each(function() { this.checked = true; });
					}
					if (sortColumn) {
						sortDirection = sortDirection ? sortDirection[1] : 'DESC';
						$('#display-order-column').val(sortColumn[1] + '|' + sortDirection);
					}
					if (count)
						$('#number-to-display').val(count[1]);
				}
				
				this.changeTab(linkId ? 'pre-saved-links' : 'quick-search');
			}
			
			$('#area-type').change(this.loadAreasByType);
			this.loadAreasByType(area ? escape(area[2]) : null);
		},
		loadAreasByType: function(areaToSetAfterLoad) {
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
						
						if (/"/.test(areaName))
							continue;
						
						if (areaName.length > 20)
							printableAreaName = $('<div/>').text(areaName.substr(0, 20) + '... (' + String(listingCount) + ')').html();
						else
							printableAreaName = $('<div/>').text(areaName + ' (' + String(listingCount) + ')').html();
						options.push('<option value="' + urlEscapedAreaName + '">' + printableAreaName + '</option>');
					}
					$('#area-name').html(options.join(''));
					
					if (areaToSetAfterLoad)
						$('#area-name').val(areaToSetAfterLoad);
				}
			});
		},
		changeTab: function(type) {
			multiListingType = type;
			if (multiListingType == 'quick-search')
				mcTabs.displayTab('custom_search_tab', 'custom_search_panel');
			else if (multiListingType == 'pre-saved-links')
				mcTabs.displayTab('saved_links_tab', 'saved_links_panel');
		},
		insert: function() {
			var shortcode = '<p>[idx-listings';
			var minPrice, maxPrice, checkedPropertyTypes, sortOrder, count;
			
			minPrice = parseInt($('#min-price').val());
			maxPrice = parseInt($('#max-price').val());
			checkedPropertyTypes = $('#property-type-container input:checked').map(function() { return this.value; }).get().join(',');
			sortOrder = $('#display-order-column').val().split('|');
			count = parseInt($('#number-to-display').val());
			
			if (multiListingType == 'quick-search') {
				shortcode += ' ' + $('#area-type').val() + '="' + unescape($('#area-name').val()) + '"';
				if (!isNaN(minPrice) && minPrice > 0)
					shortcode += ' minprice="' + minPrice + '"';
				if (!isNaN(maxPrice) && maxPrice > 0)
					shortcode += ' maxprice="' + maxPrice + '"';
				if (checkedPropertyTypes)
					shortcode += ' propertytypes="' + checkedPropertyTypes + '"';
				shortcode += ' orderby="' + sortOrder[0] + '" orderdir="' + sortOrder[1] + '"';
			} else if (multiListingType == 'pre-saved-links') {
				shortcode += ' linkid="' + $('#saved-link').val() + '"';
			}

			if (!isNaN(count) && count > 0)
				shortcode += ' count="' + count + '"';
			
			shortcode += ']</p>';
			
			tinyMCEPopup.editor.execCommand(nodeEditing ? 'mceReplaceContent' : 'mceInsertContent', false, shortcode);
			tinyMCEPopup.close();
		}
	};
	
	return returnObj;
})();

tinyMCEPopup.onInit.add(dsidxMultiListings.init, dsidxMultiListings);
