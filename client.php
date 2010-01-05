<?php
add_filter("posts_request", "dsSearchAgent_Client::ClearQuery");
add_filter("the_posts", "dsSearchAgent_Client::Activate");

class dsSearchAgent_Client {
	static $Options = null;
	static $CanonicalUri = null;
	static $QueryStringTranslations = array(
		"a" => "action",
		"q" => "query",
		"d" => "directive"
	);
	
	static function Activate($posts) {
		global $wp_query;
		
		add_action("wp_head", "dsSearchAgent_Client::HeaderUnconditional");
		add_filter("style_loader_tag", "dsSearchAgent_Client::FilterVersionForAssets");
		add_filter("script_loader_src", "dsSearchAgent_Client::FilterVersionForAssets");
		wp_enqueue_script("jquery");
		
		// for remote debugging while this plugin is in beta
		if ($_SERVER["REMOTE_ADDR"] == "70.168.154.66") {
			if ($_GET["debug-wpquery"]) {
				print_r($wp_query);
				print_r("\n");
			}
			if ($_GET["debug-posts"]) {
				print_r($posts);
				print_r("\n");
			}
			if ($_GET["debug-php"]) {
				phpinfo();
				exit();
			}
		}
		
		if (!is_array($wp_query->query) || !isset($wp_query->query["idx-action"]))
			return $posts;
		
		$action = $wp_query->query["idx-action"];
		add_action("wp_head", "dsSearchAgent_Client::Header");
		
		// keep wordpress from mucking up our HTML
		remove_filter("the_content", "wptexturize");
		remove_filter("the_content", "convert_smilies");
		remove_filter("the_content", "convert_chars");
		remove_filter("the_content", "wpautop");
		remove_filter("the_content", "prepend_attachment");
		
		// we handle our own redirects and canonicals
		add_filter("wp_redirect", "dsSearchAgent_Client::CancelAllRedirects");
		add_filter("redirect_canonical", "dsSearchAgent_Client::CancelAllRedirects");
		
		// we don't support RSS feeds just yet
		remove_action("wp_head", "feed_links");
		remove_action("wp_head", "feed_links_extra");
		
		$wp_query->found_posts = 0;
		$wp_query->max_num_pages = 0;
		$wp_query->is_page = 1;
		$wp_query->is_home = null;
		$wp_query->is_singular = 1;
		
		$apiParams = array();
		foreach (array_merge($wp_query->query_vars, $_GET) as $key => $value) {
			if (strpos($key, "idx-q") === false && strpos($key, "idx-d") === false)
				continue;
			
			$key = str_replace(array("-", "<", ">"), array(".", "[", "]"), substr($key, 4));
			$key = self::$QueryStringTranslations[substr($key, 0, 1)] . substr($key, strpos($key, "."));
			$value = str_replace("_", "-", str_replace("-", " ", $value));
			
			$apiParams[(string)$key] = $value;
		}
		
		if (strtolower($wp_query->query["idx-action"]) == "results") {
			if ($apiParams["query.LinkID"])
				$apiParams["query.ForceUsePropertySearchConstraints"] = "true";
			$apiParams["directive.ResultsPerPage"] = 25;
			if ($apiParams["directive.ResultPage"])
				$apiParams["directive.ResultPage"] = $apiParams["directive.ResultPage"] - 1;
			$apiParams["responseDirective.IncludeMetadata"] = "true";
			$apiParams["responseDirective.IncludeLinkMetadata"] = "true";
		}
		$apiParams["responseDirective.IncludeDisclaimer"] = "true";
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData($wp_query->query["idx-action"], $apiParams, false);
		if ($apiHttpResponse["response"]["code"] == "404")
			return array();
		else if (!empty($apiHttpResponse["errors"]) || substr($apiHttpResponse["response"]["code"], 0, 1) == "5")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the real estate data. Please check back soon.", "Real estate data load error");
		else
			$apiData = $apiHttpResponse["body"];
		
		$title = self::ExtractValueFromApiData($apiData, "title");
		$dateaddedgmt = self::ExtractValueFromApiData($apiData, "dateaddedgmt");
		self::$CanonicalUri = self::ExtractValueFromApiData($apiData, "canonical");
		self::EnsureBaseUri();
		
		$posts = array((object)array(
			"ID"				=> -1,
			"comment_count"		=> 0,
			"comment_status"	=> "closed",
			"ping_status"		=> "closed",
			"post_author"		=> 1,
			"post_content"		=> $apiData,
			"post_date"			=> $dateaddedgmt ? $dateaddedgmt : date("c"),
			"post_date_gmt"		=> $dateaddedgmt ? $dateaddedgmt : gmdate("c"),
			"post_name"			=> "idx-data",
			"post_parent"		=> -1,
			"post_status"		=> "publish",
			"post_title"		=> $title,
			"post_type"			=> "page"
		));
		return $posts;
	}
	static function ExtractValueFromApiData($apiData, $key) {
		preg_match('/^\<!\-\-\s*' . $key . ':\s*"(?P<value>.+)"\s*\-\-\>/m', $apiData, $matches);
		return $matches["value"];
	}
	static function EnsureBaseUri() {
		$urlSlug = dsSearchAgent_Rewrite::GetUrlSlug();
		$queryPosition = strrpos(self::$CanonicalUri, "?");
		if ($queryPosition)
			$hardPermalink = substr(self::$CanonicalUri, 0, $queryPosition);
		else
			$hardPermalink = self::$CanonicalUri;
		
		$requestedPath = $_SERVER["REQUEST_URI"];
		$queryPosition = strrpos($requestedPath, "?");
		if ($queryPosition)
			$requestedPath = substr($requestedPath, 0, $queryPosition);
		else
			$requestedPath = $requestedPath;
		
		$blogUrlWithoutProtocol = str_replace("http://", "", get_bloginfo("url"));
		$blogUrlDirIndex = strpos($blogUrlWithoutProtocol, "/");
		
		if ($blogUrlDirIndex) // don't need to check for !== false here since WP prevents trailing /'s
			$blogUrlDir = substr($blogUrlWithoutProtocol, strpos($blogUrlWithoutProtocol, "/"));
		
		if ($requestedPath != $blogUrlDir . "/" . $urlSlug . $hardPermalink) {
			$redirect = $blogUrlDir . "/" . $urlSlug . self::$CanonicalUri;
			$sortColumnKey = "idx-d-SortColumn<0>";
			$sortDirectionKey = "idx-d-SortDirection<0>";
			$sortColumn = $_GET[$sortColumnKey];
			$sortDirection = $_GET[$sortColumnDirection];
			
			if ($sortColumn && $sortDirection) {
				if (substr($redirect, strlen($redirect) - 1, 1) == "/")
					$redirect .= "?";
				else
					$redirect .= "&";
				$redirect .= urlencode($sortColumnKey) . "=" . urlencode($sortColumn) . "&" . urlencode($sortDirectionKey) . "=" . urlencode($sortDirection);
			}
			
			header("Location: $redirect", true, 301);
			exit();
		}
	}
	static function ClearQuery($query) {
		global $wp_query;
		
		if(!is_array($wp_query->query) || !isset($wp_query->query["idx-action"]))
			return $query;
		
		return "";
	}
	static function CancelAllRedirects($location) {
		return false;
	}
	static function HeaderUnconditional() {
		global $dsSearchAgent_PluginUrl;
		echo "<link rel=\"stylesheet\" href=\"{$dsSearchAgent_PluginUrl}css/client.css\" />";
	}
	static function Header() {
		global $dsSearchAgent_PluginUrl;
		$blogUrl = get_bloginfo("url");
		$urlSlug = dsSearchAgent_Rewrite::GetUrlSlug();
		$canonicalUri = self::$CanonicalUri;
		
		if ($canonicalUri)
			echo "<link rel=\"canonical\" href=\"{$blogUrl}/{$urlSlug}{$canonicalUri}\" />\n";
	}
	static function FilterVersionForAssets($string) {
		if (!strpos($string, ".diversesolutions.com/"))
			return $string;
		else
			return str_replace(array("?#038;ver=-", "?ver=-", "&#038;ver=-", "&ver=-"), array("?", "?", "", ""), $string);
	}
}
?>