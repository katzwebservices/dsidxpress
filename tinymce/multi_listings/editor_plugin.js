tinymce.create('tinymce.plugins.dsidxListings', {
	init : function(ed, url) {
		ed.addCommand('dsidx-listings', function() {
			ed.windowManager.open({
				file : url + '/dialog.php',
				width : 380,
				height : 530,
				inline : 1
			}, {
				plugin_url : url
			});
		});
		ed.addButton('idxlistings', {
			title : 'Insert listings from MLS data (by dsSearchAgent)',
			cmd : 'dsidx-listings',
			image : url + '/img/multi_listings.png'
		});
		ed.onNodeChange.add(function(ed, cm, n) {
			cm.setActive('idxlistings', /^\[idx-listings /.test(n.innerHTML));
		});
	},
	createControl : function(n, cm) {
		return null;
	},
	getInfo : function() {
		return {
			longname : 'Insert "live" listings from MLS data (by dsIDXpress)',
			author : 'Diverse Solutions',
			authorurl : 'http://www.diversesolutions.com',
			infourl : 'javascript:void(0)',
			version : "1.0"
		};
	}
});
tinymce.PluginManager.add('idxlistings', tinymce.plugins.dsidxListings);