<?php
class dsSearchAgent_ApiRequest {
	private static $ApiEndPoint = "http://api.idx.diversesolutions.com/api/";
	// do NOT change this value or you will be automatically banned from the API. since the data is only updated every two hours, and
	// since these API calls are computationally intensive on our servers, we need to set a reasonable cache duration. 
	private static $CacheSeconds = 7200;
	private static $NumericValues = array(
		"query.PriceMin",
		"query.PriceMax",
		"query.ImprovedSqFtMin",
		"query.BedsMin",
		"query.BathsMin"
	);
	
	static function FetchData($action, $params = array(), $echoAssetsIfNotEnqueued = true, $cacheSecondsOverride = null, $options = null) {
		global $wp_query, $wp_version;
		global $dsSearchAgent_PluginVersion;
		
		$options = $options ? $options : get_option("dssearchagent-wordpress-edition");
		$privateApiKey = $options["PrivateApiKey"];
		$requestUri = self::$ApiEndPoint . $action;
		
		$params["query.SearchSetupID"] = $options["SearchSetupID"];
		$params["requester.AccountID"] = $options["AccountID"];
		$params["requester.ApplicationProfile"] = "WordPressIdxModule";
		$params["requester.ApplicationVersion"] = $wp_version;
		$params["requester.PluginVersion"] = $dsSearchAgent_PluginVersion;
		$params["requester.RequesterUri"] = get_bloginfo("url");
		
		foreach (self::$NumericValues as $key) {
			if (array_key_exists($key, $params))
				$params[$key] = str_replace(",", "", $params[$key]);
		}
		
		ksort($params);
		$transientKey = "idx_" . sha1($action . "_" . implode("", $params));
		
		if ($cacheSecondsOverride !== 0) {
			$cachedRequestData = get_transient($transientKey);
			if ($cachedRequestData) {
				$cachedRequestData["body"] = self::ExtractAndEnqueueStyles($cachedRequestData["body"], $echoAssetsIfNotEnqueued);
				$cachedRequestData["body"] = self::ExtractAndEnqueueScripts($cachedRequestData["body"], $echoAssetsIfNotEnqueued);
				return $cachedRequestData;
			}
		}
		
		// these params need to be beneath the caching stuff since otherwise the cache will be useless 
		$params["requester.ClientIpAddress"] = $_SERVER["REMOTE_ADDR"];
		$params["requester.ClientUserAgent"] = $_SERVER["HTTP_USER_AGENT"];
		$params["requester.UrlReferrer"] = $_SERVER["HTTP_REFERER"];
		$params["requester.UtcRequestDate"] = gmdate("c");
		
		ksort($params);
		$stringToSign = "";
		foreach ($params as $key => $value) {
			$stringToSign .= "$key:$value\n";
			if (!$params[$key])
				$params[$key] = "";
		}
		$stringToSign = rtrim($stringToSign, "\n");
		
		$params["requester.Signature"] = hash_hmac("sha1", $stringToSign, $privateApiKey);
		$request = new WP_Http();
		$response = (array)$request->post($requestUri, array(
			"body"			=> $params,
			"httpversion"	=> "1.1",
			"redirection"	=> "0"
		));
		
		if (empty($response["errors"]) && substr($response["response"]["code"], 0, 1) != "5") {
			$response["body"] = self::FilterData($response["body"]);
			if ($cacheSecondsOverride !== 0)
				set_transient($transientKey, $response, $cacheSecondsOverride === null ? self::$CacheSeconds : $cacheSecondsOverride);
			$response["body"] = self::ExtractAndEnqueueStyles($response["body"], $echoAssetsIfNotEnqueued);
			$response["body"] = self::ExtractAndEnqueueScripts($response["body"], $echoAssetsIfNotEnqueued);
		}
		
		return $response;
	}
	private static function FilterData($data) {
		global $wp_version;
		global $dsSearchAgent_PluginUrl, $dsSearchAgent_PluginVersion;
		
		$data = str_replace('{$pluginUrlPath}', $dsSearchAgent_PluginUrl, $data);
		$data = str_replace('{$pluginVersion}', $dsSearchAgent_PluginVersion, $data);
		$data = str_replace('{$wordpressVersion}', $wp_version, $data);
		
		$blogUrlWithoutProtocol = str_replace("http://", "", get_bloginfo("url"));
		$blogUrlDirIndex = strpos($blogUrlWithoutProtocol, "/");
		
		if ($blogUrlDirIndex) // don't need to check for !== false here since WP prevents trailing /'s
			$blogUrlDir = substr($blogUrlWithoutProtocol, strpos($blogUrlWithoutProtocol, "/"));
		$data = str_replace('{$idxActivationPath}', $blogUrlDir . "/" . dsSearchAgent_Rewrite::GetUrlSlug(), $data);
		
		return $data;
	}
	private static function ExtractAndEnqueueStyles($data, $echoAssetsIfNotEnqueued) {
		// since we 100% control the data coming from the API, we can set up a regex to look for what we need. regex
		// is never ever ideal to parse html, but since neither wordpress nor php have a HTML parser built in at the
		// time of this writing, we don't really have another choice. in other words, this is super delicate!

		preg_match_all('/<link\s*rel="stylesheet"\s*type="text\/css"\s*href="(?P<href>[^"]+)"\s*data-handle="(?P<handle>[^"]+)"\s*\/>/', $data, $styles, PREG_SET_ORDER);
		foreach ($styles as $style) {
			if (!$echoAssetsIfNotEnqueued || ($echoAssetsIfNotEnqueued && wp_style_is($style["handle"])))
				$data = str_replace($style[0], "", $data);
			wp_enqueue_style($style["handle"], $style["href"], false, "-");
		}
		
		return $data;
	}
	private static function ExtractAndEnqueueScripts($data, $echoAssetsIfNotEnqueued) {
		// see comment in ExtractAndEnqueueStyles
		
		preg_match_all('/<script\s*src="(?P<src>[^"]+)"\s*data-handle="(?P<handle>[^"]+)"><\/script>/', $data, $scripts, PREG_SET_ORDER);
		foreach ($scripts as $script) {
			if (!$echoAssetsIfNotEnqueued || ($echoAssetsIfNotEnqueued && wp_script_is($script["handle"])))
				$data = str_replace($script[0], "", $data);
			wp_enqueue_script($script["handle"], $script["src"], false, "-");
		}
		
		return $data;
	}
}
?>