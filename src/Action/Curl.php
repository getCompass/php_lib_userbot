<?php declare(strict_types=1);

namespace GetCompass\Userbot\Action;

use GetCompass\Userbot\Exception\Request\BadRequestException;
use GetCompass\Userbot\Exception\Request\UnexpectedResponseException;

/**
 * Simple non-psr curl wrapper.
 */
class Curl
{
    /** @var int */
    protected const NONE_MODE = 0;
    /** @var int */
    protected const USE_MULTIPART_FOR_DATA_MODE = 1 << 1;
    /** @var \CurlHandle */
    protected $curl;
    /** @var int */
    protected $response_code = 0;
    /** @var string */
    protected $user_agent = "Robot";
    /** @var array */
    protected $headers;
    /** @var int */
    protected $timeout = 5;
    /** @var int */
    protected $mode = 0;

    /**
     * Curl constructor.
     */
    public function __construct()
    {
        if (!extension_loaded("curl")) {
            throw new \RuntimeException("cURL library is not loaded");
        }

        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        if (defined("GET_COMPASS_CURL_TIMEOUT")) {
            $this->timeout = GET_COMPASS_CURL_TIMEOUT;
        }
    }

    /**
     * Curl destructor
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * Set curl mode. Affects only one request, reset to none after it.
     *
     * @param int $mode
     * @return void
     */
    public function setMode(int $mode): void
    {
        $this->mode |= $mode;
    }

    /**
     * Set header for curl.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Performs POST request to server.
     *
     * @param string $url
     * @param mixed  $params
     *
     * @return string raw http response
     *
     * @throws BadRequestException
     * @throws UnexpectedResponseException
     */
    public function post(string $url, $params = []): string
    {
        if (($this->mode & static::USE_MULTIPART_FOR_DATA_MODE) === 0 && is_array($params)) {
            $params = http_build_query($params);
        }

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);

        if (count($this->headers) > 0) {
        	curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->formatHeaders($this->headers));
        }

        return $this->exec($url);
    }

    /**
     * Formatted headers.
     *
     * @param array $headers
     *
     * @return string[]
     */
    protected function formatHeaders(array $headers): array
    {
    	  $output = [];
    	  foreach ($headers as $key => $value) {
    	  	$output[] = $key . ": " . $value;
    	  }

    	  return $output;
    }

    /**
     * Upload file by link.
     *
     * @param string $file_path
     *
     * @return \CURLFile raw curl file
     */
    public function attachFile(string $file_path): \CURLFile
    {
        $this->setMode(static::USE_MULTIPART_FOR_DATA_MODE);
        return new \CURLFile($file_path);
    }

    /**
     * Returns http-code.
     *
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->response_code;
    }

    /**
     * Execute response to server.
     *
     * @param string $url
     * @return string
     *
     * @throws UnexpectedResponseException
     * @throws BadRequestException
     */
    protected function exec(string $url): string
    {
        if ($url === "") {
            throw new BadRequestException("empty url passes");
        }

        // set some curl options
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->curl, CURLOPT_URL, $url);

        $response   = curl_exec($this->curl);
        $this->mode = static::NONE_MODE;

        if (curl_errno($this->curl) !== CURLE_OK) {
            throw new UnexpectedResponseException("curl error occurred: " . curl_error($this->curl));
        }

        if ($response === false) {
            throw new UnexpectedResponseException("response is empty");
        }

        return $this->parseResponse($response);
    }

    /**
     * Parses server response.
     *
     * @param string $response
     *
     * @return false|string
     * @mixed
     */
    protected function parseResponse(string $response)
    {
        $this->response_code = (int)curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $header_size         = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

        return substr($response, $header_size);
    }
}