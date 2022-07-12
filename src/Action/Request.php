<?php declare(strict_types=1);

namespace GetCompass\Userbot\Action;

use GetCompass\Userbot\Exception\Request\BadRequestException;
use GetCompass\Userbot\Exception\Request\RequestInProgressException;
use GetCompass\Userbot\Exception\Request\UnexpectedResponseException;
use GetCompass\Userbot\Security\Credentials;
use GetCompass\Userbot\Security\PayloadHandler;

/**
 * Simple userbot-api decorator.
 */
class Request
{
    /** @var int use unsigned payload, file uploading uses unsigned request */
    protected const USE_UNSIGNED_PAYLOAD = 1 << 1;

    /** @var Curl */
    protected $client;
    /** @var Credentials */
    protected $credentials;
    /** @var string */
    protected $url = "";
    /** @var array */
    protected $rawPayload = [];
    /** @var string */
    protected $requestId = "";
    /** @var array */
    protected $response;
    /** @var int request signing mode */
    protected $mode = 0;

    /**
     * Request constructor.
     *
     * @param Curl        $client
     * @param Credentials $credentials
     */
    public function __construct(Curl $client, Credentials $credentials)
    {
        $this->client      = $client;
        $this->credentials = $credentials;
    }

    /**
     * Sets payload signing mode.
     * When false payload won't be signed on call.
     *
     * @param bool $needSign
     * @return $this
     */
    public function withSign(bool $needSign = true): self
    {
        $this->mode = $needSign === true
            ? $this->mode & ~static::USE_UNSIGNED_PAYLOAD
            : $this->mode | static::USE_UNSIGNED_PAYLOAD;

        return $this;
    }

    /**
     * Sets request address.
     * Multiple calls override the existing one.
     *
     * @param string $url
     * @return $this
     */
    public function withAddress(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Sets request message.
     * Multiple calls override the existing one.
     *
     * @param array $payload
     *
     * @return $this
     */
    public function withMessage(array $payload = []): self
    {
        $this->rawPayload = $payload;
        return $this;
    }

    /**
     * Attach a file to the request.
     * When called multiple times, the file will be overwritten.
     *
     * @param string $filePath
     *
     * @return $this
     */
    public function withFile(string $filePath): self
    {
        $this->rawPayload["file"] = $this->client->attachFile($filePath);
        return $this;
    }

    /**
     * Sends prepared message to the server.
     * Fails, if message is incorrect.
     *
     * Doesn't return server response.
     * To get response waitResponse should be called.
     *
     * @return $this
     *
     * @throws BadRequestException
     * @throws UnexpectedResponseException
     */
    public function send(): self
    {
        if ($this->url === "") {
            throw new \RuntimeException("url can not be empty");
        }

        try {

            $response = $this->exec();
        } catch (RequestInProgressException $e) {

            // UnexpectedResponseException is unexpected here, need change it
            throw new UnexpectedResponseException($e->getMessage(), $e->getCode());
        }

        if (isset($response["request_id"])) {

            // set request id if server has returned it
            $this->requestId = $response["request_id"];
        } else {

            // if there is no request id, just set the response
            $this->response = $response;
        }

        return $this;
    }

    /**
     * Waits till server completes the request.
     * Fails if request time out.
     *
     * @param int $timeout
     *
     * @return array
     *
     * @throws BadRequestException
     * @throws UnexpectedResponseException
     */
    public function waitResponse(int $timeout = 10): array
    {
        if (!is_null($this->response)) {
            return $this->response;
        }

        if ($this->requestId === "") {
            throw new \RuntimeException("request has no request id to wait");
        }

        // set request deadline
        $deadlineDate = time() + $timeout;

        do {

            try {

                return (new static($this->client, $this->credentials))
                    ->withSign()
                    ->withAddress(UrlProvider::requestStatus())
                    ->withMessage(["request_id" => $this->requestId])
                    ->exec();
            } catch (RequestInProgressException $e) {

                // code 7 — request is not ready yet, need to wait a bit
                // just do nothing and wait till next iteration
            }

            sleep(1);
        } while (time() < $deadlineDate);

        // timeout reached, request wasn't processed by server in expected time
        throw new UnexpectedResponseException("request waiting timeout");
    }

    # region shared protected

    /**
     * Send request to the server.
     *
     * @return array
     *
     * @throws BadRequestException
     * @throws RequestInProgressException
     * @throws UnexpectedResponseException
     */
    protected function exec(): array
    {
        if (($this->mode & self::USE_UNSIGNED_PAYLOAD) === self::USE_UNSIGNED_PAYLOAD) {

            $rawResponse = $this->client->post($this->url, $this->rawPayload);
        } else {

            $payload     = $this->rawPayload;
            $rawResponse = $this->client->post($this->url, PayloadHandler::encode($this->credentials, $payload));
        }

        $this->checkResponseHttpCode();
        return $this->parseCompassResponse($rawResponse);
    }

    /**
     * Checks request http code.
     * Only http 200 is valid, any other will trigger exception.
     *
     * @return void
     * @throws UnexpectedResponseException
     */
    protected function checkResponseHttpCode(): void
    {
        if ($this->client->getHttpCode() === 200) {
            return;
        }

        // other codes can't be processed
        throw new UnexpectedResponseException("response is not ok, got http ", $this->client->getHttpCode());
    }

    /**
     * Parses useful info from response body.
     * Error codes will be processed here as well.
     *
     * Response body must be valid json string.
     *
     * @param string $rawResponse
     *
     * @return array
     *
     * @throws BadRequestException
     * @throws RequestInProgressException
     * @throws UnexpectedResponseException
     */
    protected function parseCompassResponse(string $rawResponse): array
    {
        $response = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedResponseException("response must be json file");
        }

        if (!isset($response["status"])) {
            throw new UnexpectedResponseException("response has no status field");
        }

        if (!isset($response["response"])) {
            throw new UnexpectedResponseException("response has no response field");
        }

        // throw exception, if response status is not ok
        if ($response["status"] !== "ok") {

            if (!isset($response["response"]["error_code"])) {
                throw new UnexpectedResponseException("response is not ok, but there is error code on response data");
            }

            static::processSystemError(
                (int)$response["response"]["error_code"],
                $response["response"]["message"] ?? "no error message"
            );
        }

        return $response["response"];
    }

    /**
     * Handle exception by response error code.
     *
     * @param int    $errorCode
     * @param string $message
     *
     * @return void
     *
     * @throws UnexpectedResponseException
     * @throws BadRequestException
     * @throws RequestInProgressException
     */
    protected static function processSystemError(int $errorCode, string $message): void
    {
        if (BadRequestException::isMyCode($errorCode)) {
            throw new BadRequestException($message, $errorCode);
        }

        if (RequestInProgressException::isMyCode($errorCode)) {
            throw new RequestInProgressException($message, $errorCode);
        }

        // unknown code thrown unexpected exception
        throw new UnexpectedResponseException($message, $errorCode);
    }

    # endregion shared protected
}
