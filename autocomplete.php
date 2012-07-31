<?php
add_action('init', array('dsidxpress_autocomplete', 'RegisterScripts'));

class dsidxpress_autocomplete {
	public static function RegisterScripts() {
		// register auto-complete script & style for use outside the plugin
		wp_register_script('dsidx-autocomplete', plugins_url('js/autocomplete.js', __FILE__), array('jquery-ui-autocomplete'), DSIDXPRESS_PLUGIN_VERSION, true);
		wp_register_style('dsidx-autocomplete-css', plugins_url('css/jquery-ui-1.8.21-autocomplete.css', __FILE__), null, DSIDXPRESS_PLUGIN_VERSION);
	}
	
	public static function AddScripts($needs_plugin_url = true) {
		wp_enqueue_script('dsidx-autocomplete');
		wp_enqueue_style('dsidx-autocomplete-css');
		
		if ($needs_plugin_url) {
			$plugin_url =  get_home_url() . '/wp-content/plugins/dsidxpress/';
			echo <<<HTML
				<script type="text/javascript">
				if (typeof localdsidx == "undefined" || !localdsidx) { var localdsidx = {}; };
				localdsidx.pluginUrl = "{$plugin_url}";
				</script>
HTML;
		}
	}
}
