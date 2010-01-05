tinymce.create('tinymce.plugins.dsidxListing', {
	init : function(ed, url) {
		ed.addCommand('dsidx-listing', function() {
			ed.windowManager.open({
				file : url + '/dialog.htm',
				width : 320,
				height : 120,
				inline : 1
			}, {
				plugin_url : url, // Plugin absolute URL
				some_custom_arg : 'custom arg' // Custom argument
			});
		});
		ed.addButton('idxlisting', {
			title : 'Insert single listing from MLS data (by dsSearchAgent)',
			cmd : 'dsidx-listing',
			image : url + '/img/single_listing.png'
		});
		ed.onNodeChange.add(function(ed, cm, n) {
			if (!/^\[idx-listing /.test(n.innerHTML))
				return;
			//cm.setActive('idxlistings', n.nodeName == 'IMG');
		});
	},
	createControl : function(n, cm) {
		return null;
	},
	getInfo : function() {
		return {
			longname : 'Insert single listing from MLS data (by dsSearchAgent)',
			author : 'Diverse Solutions',
			authorurl : 'http://www.diversesolutions.com',
			infourl : 'javascript:void(0)',
			version : "1.0"
		};
	}
});
tinymce.PluginManager.add('idxlisting', tinymce.plugins.dsidxListing);