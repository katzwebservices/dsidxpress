<?php
class dsidxpress_seo {
	var $title;
	var $description;
	var $keywords;

	function __construct ($ds_title, $ds_description, $ds_keywords) {
		if (strlen($ds_title) > 0)
			$this->title = $ds_title;
		if (strlen($ds_description) > 0)
			$this->description = $ds_description;
		if (strlen($ds_keywords) > 0)
			$this->keywords = $ds_keywords;
	}


	function dsidxpress_head_action() {
		$meta_string = '';

		//Keyword Section
		if (isset($this->keywords))
			$meta_string .= sprintf("<meta name=\"keywords\" content=\"%s\" />", $this->keywords);
		//End Keyword Section

		//Description Section
		if (isset($this->description))
			$meta_string .= sprintf("<meta name=\"description\" content=\"%s\" />", $this->description);
		//End Description Section

		/**
		 * Modify the meta tags
		 * @param string Meta tag HTML to echo
		 * @param dsidxpress_seo $this Current object
		 */
		$meta_string = apply_filters( 'dsidxpress_seo_meta_string', $meta_string, $this );

		if(!empty($meta_string)){
			echo "\n<!-- IDX SEO settings -->\n";
			echo "$meta_string\n";
			echo "<!-- /IDX SEO settings -->\n";
		}
		remove_action('wp_head', array($this, 'dsidxpress_head_action'));
	}

	function dsidxpress_title_filter($title) {

		if($this->title) {
			$title = $this->title . ' | ';
		}

		/**
		 * Modify the title
		 * @param string Title to return
		 * @param dsidxpress_seo $this Current object
		 */
		$title = apply_filters( 'dsidxpress_seo_title', $title, $this );

		return $title;
	}
}
?>