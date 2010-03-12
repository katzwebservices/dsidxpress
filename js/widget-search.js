dsWidgetSearch = {
	InitFields : function(widget_id){
		var $ = jQuery;

		$('#'+widget_id+'-show_checkboxes input').each(function(index, value){
			var block_id = '#' + value.id.replace('-show_','-') + '_block';
			
			if(value.checked) $(block_id).show();
			else $(block_id).hide();
		});
		
		$('#'+widget_id+'-show_checkboxes input').click(function(){
			var block_id = '#' + this.id.replace('-show_','-') + '_block';
			
			if(this.checked) $(block_id).show();
			else $(block_id).hide();
		});
	},
	
	LaunchLookupList : function(url){
		window.open(url, 'wpdslookuptypes', 'width=400,height=600,menubar=no,toolbar=no,location=no,resizable=yes,scrollbars=yes');
	}
}