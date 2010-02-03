<?php
add_action("pre_get_posts", "dsSearchAgent_Client::PreActivate");
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
	static $DebugAllowedFrom = "70.168.154.66";

	// this is a roundabout way to make sure that any other plugin / widget / etc that uses the WP_Query object doesn't get our IDX data
	// in their query. since we don't actually get the query itself in the "the_posts" filter, we have to step around the issue by
	// checking it BEFORE it gets to the the_posts filter. later, in the the_posts filter, we restore the previous state of things.
	static function PreActivate($q) {
		global $wp_query;

		if (!is_array($q->query) || $q->query["suppress_filters"])
			return;

		if ($wp_query->query["idx-action"] && !$q->query["idx-action"]) {
			$wp_query->query["idx-action-swap"] = $wp_query->query["idx-action"];
			unset($wp_query->query["idx-action"]);
		}
	}
	static function Activate($posts) {
		global $wp_query;

		// we're going to make our own _corrected_ array for the superglobal $_GET due to bugs in the "preferred" way to host WP on windows w/ IIS 6.
		// the reason for this is because the URL that handles the request becomes wp-404-handler.php and _SERVER["QUERY_STRING"] subsequently ends up
		// being in the format of 404;http://<domain>:<port>/<url>?<query-arg-1>&<query-arg-2>. the result of that problem is that the first query arg
		// ends up becoming the entire request url up to the second query param
		$get = $_GET;
		$getKeys = array_keys($get);
		if (strpos($getKeys[0], "404;") === 0) {
			$get[substr($getKeys[0], strpos($getKeys[0], "?") + 1)] = $get[$getKeys[0]];
			unset($get[$getKeys[0]]);
		}

		// for remote debugging
		if ($_SERVER["REMOTE_ADDR"] == self::$DebugAllowedFrom) {
			if ($get["debug-wpquery"]) {
				print_r($wp_query);
				exit();
			}
			if ($get["debug-posts"]) {
				print_r($posts);
				exit();
			}
			if ($get["debug-plugins"]) {
				foreach (get_option("active_plugins") as $plugin) {
					print_r(get_plugin_data(WP_CONTENT_DIR . "/plugins/$plugin"));
					print_r("\n");
				}
				exit();
			}
			if ($get["debug-php"]) {
				phpinfo();
				exit();
			}
			if ($get["flush-idx-transients"]) {
				global $wpdb;
				$wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE option_name LIKE '_transient_idx_%' OR option_name LIKE '_transient_timeout_idx_%'");
			}
		}

		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (!$options["Activated"])
			return $posts;

		add_action("wp_head", "dsSearchAgent_Client::HeaderUnconditional");
		wp_enqueue_script("jquery");

		// see comment above PreActivate
		if (is_array($wp_query->query) && isset($wp_query->query["idx-action-swap"])) {
			$wp_query->query["idx-action"] = $wp_query->query["idx-action-swap"];
			unset($wp_query->query["idx-action-swap"]);
			return $posts;
		}

		if (!is_array($wp_query->query) || !isset($wp_query->query["idx-action"])) {
			return $posts;
		}

		$action = strtolower($wp_query->query["idx-action"]);
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
		add_filter("page_link", "dsSearchAgent_Client::GetPermalink"); // for any plugin that needs it

		// "All in One SEO Pack" tries to do its own canonical URLs as well. we disable them here only to prevent
		// duplicate canonical elements. even if this fell through w/ another plugin though, the page_link filter would
		// ensure that the permalink is correct
		global $aioseop_options;
		if ($aioseop_options["aiosp_can"])
			$aioseop_options["aiosp_can"] = false;

		// we don't support RSS feeds just yet
		remove_action("wp_head", "feed_links");
		remove_action("wp_head", "feed_links_extra");

		// allow wordpress to consume the page template option the user choose in the dsIDXpress settings
		if ($action == "results" && $options["ResultsTemplate"])
			wp_cache_set(0, array("_wp_page_template" => array($options["ResultsTemplate"])), "post_meta");
		else if ($action == "details" && $options["DetailsTemplate"])
			wp_cache_set(0, array("_wp_page_template" => array($options["DetailsTemplate"])), "post_meta");

		$wp_query->found_posts = 0;
		$wp_query->max_num_pages = 0;
		$wp_query->is_page = 1;
		$wp_query->is_home = null;
		$wp_query->is_singular = 1;

		$apiParams = array();
		foreach (array_merge($wp_query->query_vars, $get) as $key => $value) {
			if (strpos($key, "idx-q") === false && strpos($key, "idx-d") === false)
				continue;

			$key = str_replace(array("-", "<", ">"), array(".", "[", "]"), substr($key, 4));
			$key = self::$QueryStringTranslations[substr($key, 0, 1)] . substr($key, strpos($key, "."));
			$value = str_replace("_", "-", str_replace("-", " ", $value));

			$apiParams[(string)$key] = $value;
		}

		if ($action == "results") {
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

		if ($_SERVER["REMOTE_ADDR"] == self::$DebugAllowedFrom) {
			if ($get["debug-api-response"]) {
				print_r($apiHttpResponse);
				exit();
			}
		}

		if ($apiHttpResponse["response"]["code"] == "404")
			return array();
		else if (empty($apiHttpResponse["body"]) || !empty($apiHttpResponse["errors"]) || substr($apiHttpResponse["response"]["code"], 0, 1) == "5")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the real estate data. Please check back soon.", "Real estate data load error");
		else
			$apiData = $apiHttpResponse["body"];

		$title = self::ExtractValueFromApiData($apiData, "title");
		$dateaddedgmt = self::ExtractValueFromApiData($apiData, "dateaddedgmt");
		$description = self::ExtractValueFromApiData($apiData, "description");
		self::$CanonicalUri = self::ExtractValueFromApiData($apiData, "canonical");
		self::EnsureBaseUri();

		set_query_var("name", "dsidxpress-{$action}"); // at least a few themes require _something_ to be set here to display a good <title> tag
		set_query_var("pagename", "dsidxpress-{$action}"); // setting pagename in case someone wants to do a custom theme file for this "page"
		$posts = array((object)array(
			"ID"				=> -1,
			"comment_count"		=> 0,
			"comment_status"	=> "closed",
			"ping_status"		=> "closed",
			"post_author"		=> 1,
			"post_content"		=> $apiData,
			"post_date"			=> $dateaddedgmt ? $dateaddedgmt : date("c"),
			"post_date_gmt"		=> $dateaddedgmt ? $dateaddedgmt : gmdate("c"),
			"post_excerpt"		=> $description,
			"post_name"			=> "dsidxpress-data",
			"post_parent"		=> 0,
			"post_status"		=> "publish",
			"post_title"		=> $title,
			"post_type"			=> "page"
		));
		return $posts;
	}
	static function ExtractValueFromApiData(&$apiData, $key) {
		preg_match('/^\<!\-\-\s*' . $key . ':\s*"(?P<value>[^"]+)"\s*\-\-\>/ms', $apiData, $matches);
		if ($matches[0])
			$apiData = str_replace($matches[0], "", $apiData);
		return $matches["value"];
	}
	static function EnsureBaseUri() {
		$urlSlug = dsSearchAgent_Rewrite::GetUrlSlug();
		$queryPosition = strrpos(self::$CanonicalUri, "?");
		if ($queryPosition !== false)
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
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		echo "<link rel=\"stylesheet\" href=\"{$pluginUrl}css/client.css\" />\n";
	}
	static function GetPermalink($incomingLink = null) {
		$blogUrl = get_bloginfo("url");
		$urlSlug = dsSearchAgent_Rewrite::GetUrlSlug();
		$canonicalUri = self::$CanonicalUri;

		if (isset($canonicalUri) && (!$incomingLink || preg_match("/dsidxpress-data/", $incomingLink)))
			return "{$blogUrl}/{$urlSlug}{$canonicalUri}";
		else
			return $incomingLink;
	}
	static function Header() {
		global $thesis;

		// let thesis handle the canonical
		if (self::$CanonicalUri && !$thesis)
			echo "<link rel=\"canonical\" href=\"" . self::GetPermalink() . "\" />\n";
	}
}
?>