
var dsidxSingleListing = {
	init: function() {
		var selectedContent = tinyMCEPopup.editor.selection.getContent({format : 'text'});
		var customWindowArg = tinyMCEPopup.getWindowArg('some_custom_arg');
		
		jQuery('#show-all').change(dsidxSingleListing.toggleShowAll);
	},
	insert: function() {
		var mlsNumber = jQuery('#mls-number').val();
		var shortcode = '[idx-listing mlsnumber="' + mlsNumber + '" showall="true"]';
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, shortcode);
		tinyMCEPopup.close();
	},
	toggleShowAll: function() {
		var checkbox = jQuery(this);
		var othersDisabled = checkbox.is(":checked"); 
		
		jQuery('#data-show-options input:checkbox').not(checkbox).each(function() {
			this.checked = true;
			this.disabled = othersDisabled;
		});
	}
};

tinyMCEPopup.onInit.add(dsidxSingleListing.init, dsidxSingleListing);
