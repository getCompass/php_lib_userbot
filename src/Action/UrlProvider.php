<?php declare(strict_types = 1);

namespace GetCompass\Userbot\Action;

/**
 * Small url helper.
 * Generates userbot-api endpoint urls.
 */
class UrlProvider {

	public const PROTOCOL              = "https";
	public const COMPASS_URL           = "getcompass.com";
	public const USERBOT_SUBDOMAIN_URL = "userbot";
	public const APIV3_URL             = "api/v3";

	/**
	 * @param string $route
	 * @param string|false $endpoint
	 *
	 * @return string
	 */
	public static function apiv3(string $route, string|false $endpoint = false):string
	{

		if ($endpoint !== false) {
			return sprintf("%s/%s/%s", rtrim($endpoint, "/"), static::APIV3_URL, ltrim($route, "/"));
		}

		$protocol = defined("GET_COMPASS_CURL_PROTOCOL")
			? GET_COMPASS_CURL_PROTOCOL
			: static::PROTOCOL;

		$url = defined("GET_COMPASS_CURL_URL")
			? GET_COMPASS_CURL_URL
			: static::COMPASS_URL;

		return sprintf(
			"%s://%s.%s/%s/%s",
			$protocol,
			static::USERBOT_SUBDOMAIN_URL,
			$url, static::APIV3_URL, $route
		);
	}
}