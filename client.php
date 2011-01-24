<?php
add_action("pre_get_posts", array("dsSearchAgent_Client", "PreActivate"));
add_filter("posts_request", array("dsSearchAgent_Client", "ClearQuery"));
add_filter("the_posts", array("dsSearchAgent_Client", "Activate"));

class dsSearchAgent_Client {
	static $Options = null;
	static $CanonicalUri = null;
	static $QueryStringTranslations = array(
		"a" => "action",
		"q" => "query",
		"d" => "directive"
	);
	static $DebugAllowedFrom = "70.183.17.242";

	// this is a roundabout way to make sure that any other plugin / widget / etc that uses the WP_Query object doesn't get our IDX data
	// in their query. since we don't actually get the query itself in the "the_posts" filter, we have to step around the issue by
	// checking it BEFORE it gets to the the_posts filter. later, in the the_posts filter, we restore the previous state of things.
	static function PreActivate($q) {
		global $wp_query;

		if (!is_array($wp_query->query) || !is_array($q->query) || isset($wp_query->query["suppress_filters"]) || isset($q->query["suppress_filters"])) {
			return;
		}

		if (isset($wp_query->query["idx-action"])) {
			if (!isset($q->query["idx-action"])) {
				$wp_query->query["idx-action-swap"] = $wp_query->query["idx-action"];
				unset($wp_query->query["idx-action"]);
			} else {
				$q->query_vars["caller_get_posts"] = true;
			}
		}
	}
	static function Activate($posts) {
		global $wp_query;

		// wordpress adds magic quotes for us automatically. this quoting behavior seems to be pretty old and well built in, and so we're going to
		// forcefully strip them out. see http://core.trac.wordpress.org/browser/trunk/wp-includes/load.php?rev=12732#L346 for an example of how long
		// this has existed for
		$get = stripslashes_deep($_GET);

		// we're going to make our own _corrected_ array for the superglobal $_GET due to bugs in the "preferred" way to host WP on windows w/ IIS 6.
		// the reason for this is because the URL that handles the request becomes wp-404-handler.php and _SERVER["QUERY_STRING"] subsequently ends up
		// being in the format of 404;http://<domain>:<port>/<url>?<query-arg-1>&<query-arg-2>. the result of that problem is that the first query arg
		// ends up becoming the entire request url up to the second query param

		$getKeys = array_keys($get);
		if (isset($getKeys[0]) && strpos($getKeys[0], "404;") === 0) {
			$get[substr($getKeys[0], strpos($getKeys[0], "?") + 1)] = $get[$getKeys[0]];
			unset($get[$getKeys[0]]);
		}

		// for remote debugging
		if ($_SERVER["REMOTE_ADDR"] == self::$DebugAllowedFrom) {
			if (isset($get["debug-wpquery"])) {
				print_r($wp_query);
				exit();
			}
			if (isset($get["debug-posts"])) {
				print_r($posts);
				exit();
			}
			if (isset($get["debug-plugins"])) {
				foreach (get_option("active_plugins") as $plugin) {
					print_r(get_plugin_data(WP_CONTENT_DIR . "/plugins/$plugin"));
					print_r("\n");
				}
				exit();
			}
			if (isset($get["debug-php"])) {
				phpinfo();
				exit();
			}
			if (isset($get["flush-idx-transients"])) {
				global $wpdb;
				$wpdb->query("DELETE FROM `{$wpdb->prefix}options` WHERE option_name LIKE '_transient_idx_%' OR option_name LIKE '_transient_timeout_idx_%'");
			}
		}

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$action = strtolower($wp_query->query["idx-action"]);

		if (!isset($options["Activated"]))
			return $posts;

		// Begin - code for widgets, must be on all pages
		add_action("wp_head", array("dsSearchAgent_Client", "HeaderUnconditional"));
		wp_enqueue_script("jquery");
		// End - code for widgets, must be on all pages

		// see comment above PreActivate
		if (is_array($wp_query->query) && isset($wp_query->query["idx-action-swap"])) {
			$wp_query->query["idx-action"] = $wp_query->query["idx-action-swap"];
			unset($wp_query->query["idx-action-swap"]);
			return $posts;
		}

		if (!is_array($wp_query->query) || !isset($wp_query->query["idx-action"])) {
			return $posts;
		}

		if ($action == "results" && count(self::GetApiParams($get, true)) == 0) {
			return $posts;
		}

		// keep wordpress from mucking up our HTML
		remove_filter("the_content", "wptexturize");
		remove_filter("the_content", "convert_smilies");
		remove_filter("the_content", "convert_chars");
		remove_filter("the_content", "wpautop");
		remove_filter("the_content", "prepend_attachment");

		// we handle our own redirects and canonicals
		add_filter("wp_redirect", array("dsSearchAgent_Client", "CancelAllRedirects"));
		add_filter("redirect_canonical", array("dsSearchAgent_Client", "CancelAllRedirects"));
		add_filter("page_link", array("dsSearchAgent_Client", "GetPermalink")); // for any plugin that needs it

		// "All in One SEO Pack" tries to do its own canonical URLs as well. we disable them here only to prevent
		// duplicate canonical elements. even if this fell through w/ another plugin though, the page_link filter would
		// ensure that the permalink is correct
		global $aioseop_options;
		if ($aioseop_options["aiosp_can"])
			$aioseop_options["aiosp_can"] = false;

		// we don't support RSS feeds just yet
		remove_action("wp_head", "feed_links");
		remove_action("wp_head", "feed_links_extra");

		$wp_query->found_posts = 0;
		$wp_query->max_num_pages = 0;
		$wp_query->is_page = 1;
		$wp_query->is_home = null;
		$wp_query->is_singular = 1;

		if($action == "framed")
			return self::FrameAction($action, $get);
		else
			return self::ApiAction($action, $get);
	}

	static function FrameAction($action, $get){
		global $wp_query;
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$post_id = time();

		if ($options["AdvancedTemplate"])
			wp_cache_set($post_id, array("_wp_page_template" => array($options["AdvancedTemplate"])), "post_meta");

		$description = NULL;
		$title = NULL;
		$script_code = '<script src="http://idx.diversesolutions.com/scripts/controls/Remote-Frame.aspx?MasterAccountID='. $options['AccountID'] .'&amp;SearchSetupID='. $options['SearchSetupID'] .'&amp;LinkID=0&amp;Height=2000"></script>';

		set_query_var("name", "dsidxpress-{$action}"); // at least a few themes require _something_ to be set here to display a good <title> tag
		set_query_var("pagename", "dsidxpress-{$action}"); // setting pagename in case someone wants to do a custom theme file for this "page"

		$posts = array((object)array(
			"ID"				=> $post_id,
			"comment_count"		=> 0,
			"comment_status"	=> "closed",
			"ping_status"		=> "closed",
			"post_author"		=> 1,
			"post_content"		=> $script_code,
			"post_date"			=> date("c"),
			"post_date_gmt"		=> gmdate("c"),
			"post_excerpt"		=> $description,
			"post_name"			=> "dsidxpress-data",
			"post_parent"		=> 0,
			"post_status"		=> "publish",
			"post_title"		=> $title,
			"post_type"			=> "page"
		));

		return $posts;
	}

	static function GetApiParams($get, $onlyQueryParams = false) {
		global $wp_query;

		$apiParams = array();
		foreach ($wp_query->query as $key => $value) {
			if (strpos($key, "idx-q") === false && ((!$onlyQueryParams && strpos($key, "idx-d") === false) || $onlyQueryParams))
				continue;
			if (empty($value))
				continue;

			$key = str_replace(array("-", "<", ">"), array(".", "[", "]"), substr($key, 4));
			$key = self::$QueryStringTranslations[substr($key, 0, 1)] . substr($key, strpos($key, "."));
			$value = str_replace("_", "-", str_replace("-", " ", $value));
			$value = str_replace(";amp;", "&", $value);
			$apiParams[(string)$key] = $value;
		}
		foreach ($get as $key => $value) {
			if (strpos($key, "idx-q") === false && ((!$onlyQueryParams && strpos($key, "idx-d") === false) || $onlyQueryParams))
				continue;
			if (empty($value))
				continue;

			$key = str_replace(array("-", "<", ">"), array(".", "[", "]"), substr($key, 4));
			$key = self::$QueryStringTranslations[substr($key, 0, 1)] . substr($key, strpos($key, "."));

			$apiParams[(string)$key] = $value;
		}
		return $apiParams;
	}
	static function ApiAction($action, $get) {
		global $wp_query;
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$post_id = time();

		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_style("jqueryui", "http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/themes/smoothness/jquery-ui.css");

		add_action("wp_head", array("dsSearchAgent_Client", "Header"));

		// allow wordpress to consume the page template option the user choose in the dsIDXpress settings
		if ($action == "results" && $options["ResultsTemplate"])
			wp_cache_set($post_id, array("_wp_page_template" => array($options["ResultsTemplate"])), "post_meta");
		else if ($action == "details" && $options["DetailsTemplate"])
			wp_cache_set($post_id, array("_wp_page_template" => array($options["DetailsTemplate"])), "post_meta");

		$apiParams = self::GetApiParams($get);
		if ($action == "results") {
			if (isset($apiParams["query.LinkID"]))
				$apiParams["query.ForceUsePropertySearchConstraints"] = "true";
			$apiParams["directive.ResultsPerPage"] = 25;
			if (isset($apiParams["directive.ResultPage"]))
				$apiParams["directive.ResultPage"] = $apiParams["directive.ResultPage"] - 1;
			$apiParams["responseDirective.IncludeMetadata"] = "true";
			$apiParams["responseDirective.IncludeLinkMetadata"] = "true";
		}
		$apiParams["responseDirective.IncludeDisclaimer"] = "true";

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData($wp_query->query["idx-action"], $apiParams, false);
		$apiData = $apiHttpResponse["body"];

		if ($_SERVER["REMOTE_ADDR"] == self::$DebugAllowedFrom) {
			if (isset($get["debug-api-response"])) {
				print_r($apiHttpResponse);
				exit();
			}
		}

		if ($apiHttpResponse["response"]["code"] == "404") {
			$wp_query->is_404 = true;
			return array();
		} else if ($apiHttpResponse["response"]["code"] == "302") {
			$redirect = dsSearchAgent_Client::GetBasePath() . self::ExtractValueFromApiData($apiData, "redirect");
			header("Location: $redirect", true, 302);
			exit();
		} else if (empty($apiHttpResponse["body"]) || !empty($apiHttpResponse["errors"]) || substr($apiHttpResponse["response"]["code"], 0, 1) == "5") {
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the real estate data. Please check back soon.", "Real estate data load error");
		}

		$title = self::ExtractValueFromApiData($apiData, "title");
		$dateaddedgmt = self::ExtractValueFromApiData($apiData, "dateaddedgmt");
		$description = self::ExtractValueFromApiData($apiData, "description");
		self::$CanonicalUri = self::ExtractValueFromApiData($apiData, "canonical");
		self::EnsureBaseUri();

		set_query_var("name", "dsidxpress-{$action}"); // at least a few themes require _something_ to be set here to display a good <title> tag
		set_query_var("pagename", "dsidxpress-{$action}"); // setting pagename in case someone wants to do a custom theme file for this "page"
		$posts = array((object)array(
			"ID"				=> $post_id,
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

		if(
			!self::IsStyleUrlEnqueued('jqueryui') &&
			!self::IsStyleUrlEnqueued('jquery.ui') &&
			!self::IsStyleUrlEnqueued('jquery-ui')
		) wp_enqueue_style('jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7/themes/smoothness/jquery-ui.css');

		return $posts;
	}
	static function ExtractValueFromApiData(&$apiData, $key) {
		preg_match('/^\<!\-\-\s*' . $key . ':\s*"(?P<value>[^"]+)"\s*\-\-\>/ms', $apiData, $matches);
		if (isset($matches[0])) {
			$apiData = str_replace($matches[0], "", $apiData);
			return $matches["value"];
		}
		return "";
	}
	static function EnsureBaseUri() {
		$basePath = dsSearchAgent_Client::GetBasePath();
		$queryPosition = strrpos(self::$CanonicalUri, "?");
		if ($queryPosition !== false)
			$hardPermalink = substr(self::$CanonicalUri, 0, $queryPosition);
		else
			$hardPermalink = self::$CanonicalUri;

		$requestedPath = $_SERVER["REQUEST_URI"];
		$queryPosition = strrpos($requestedPath, "?");
		if ($queryPosition !== false)
			$requestedPath = substr($requestedPath, 0, $queryPosition);
		else
			$requestedPath = $requestedPath;

		if ($requestedPath != $basePath . urldecode($hardPermalink)) {
			$redirect = $basePath . self::$CanonicalUri;
			$sortColumnKey = "idx-d-SortOrders<0>-Column";
			$sortDirectionKey = "idx-d-SortOrders<0>-Direction";
			$sortColumn = $_GET[$sortColumnKey];
			$sortDirection = $_GET[$sortDirectionKey];

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
	static function GetBasePath(){
		$urlSlug = dsSearchAgent_Rewrite::GetUrlSlug();

		$blogUrlWithoutProtocol = str_replace("http://", "", get_bloginfo("url"));
		$blogUrlDirIndex = strpos($blogUrlWithoutProtocol, "/");
		$blogUrlDir = "";
		if ($blogUrlDirIndex) // don't need to check for !== false here since WP prevents trailing /'s
			$blogUrlDir = substr($blogUrlWithoutProtocol, strpos($blogUrlWithoutProtocol, "/"));

		return $blogUrlDir . "/" . $urlSlug;
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
	static function IsStyleUrlEnqueued($partial_url){
		global $wp_styles;
		$enqueued = false;
		if ( is_a($wp_styles, 'WP_Styles') ){
			foreach($wp_styles->registered as $handle => $style){
				if(strrpos($style->src, $partial_url) !== false){
					$enqueued = true;
				}
			}
		}

		return $enqueued;
	}
}
?>