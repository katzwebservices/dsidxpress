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
	
	
	function wp_head() {
		$meta_string = '';

		//Keyword Section
		if (isset($this->keywords))
			$meta_string .= sprintf("<meta name=\"keywords\" content=\"%s\" />", $this->keywords);
		//End Keyword Section
		
		//Description Section
		if (isset($this->description))
			$meta_string .= sprintf("<meta name=\"description\" content=\"%s\" />", $this->description);
		//End Description Section
		
		if(!empty($meta_string)){
			echo "\n<!-- ZPress SEO additions -->\n";
			echo "$meta_string\n";
			echo "<!-- /ZPress SEO additions -->\n";
		}
	}
	
	function dsidxpress_title($title) {
		if($this->title)
			return $this->title . ' | ';
		return $title;
	}
}
?>