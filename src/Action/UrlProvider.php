<?php declare(strict_types=1);

namespace GetCompass\Userbot\Action;

/**
 * Small url helper.
 * Generates userbot-api endpoint urls.
 */
class UrlProvider
{
    public const PROTOCOL              = "https";
    public const COMPASS_URL           = "getcompass.com";
    public const USERBOT_SUBDOMAIN_URL = "userbot";
    public const APIV2_URL             = "api/v2";

    /**
     * @param string $route
     * @return string
     */
    public static function apiv2(string $route): string
    {
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
                $url, static::APIV2_URL, $route
        );
    }

    /**
     * Return service endpoint — userbot request status.
     * @return string
     */
    public static function requestStatus(): string
    {
        return static::apiv2("request/get");
    }
}