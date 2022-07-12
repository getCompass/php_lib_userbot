<?php declare(strict_types=1);

namespace GetCompass\Userbot\Security;

use GetCompass\Userbot\Exception\Webhook\BadRequestException;

/**
 * Userbot-api security helper.
 * Signs outgoing payload and validates incoming.
 */
class PayloadHandler
{
    /** @var string[] поля запроса и из типы */
    protected const REQUEST_FIELDS = [
        "payload"   => "array",
        "signature" => "string",
    ];

    /** @var string hash algo, sha256 is required */
    protected const HASH_ALGO = "SHA256";

    /**
     * Prepares given data for sending to Compass API.
     *
     * @param array       $payload
     * @param Credentials $credentials
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function encode(Credentials $credentials, array $payload): array
    {
        return [
            "payload"   => \json_encode($payload, JSON_FORCE_OBJECT),
            "token"     => $credentials->getApiToken(),
            "signature" => static::getPayloadSign($payload, $credentials->getApiToken(), $credentials->getSignatureKey()),
        ];
    }

    /**
     * Decodes incoming request.
     *
     * @param array       $post
     * @param Credentials $credentials
     *
     * @return array
     * @throws BadRequestException
     */
    public static function decode(Credentials $credentials, array $post): array
    {
        static::checkRequestFields($post);
        return static::getVerifiedPayload($post, $credentials->getApiToken(), $credentials->getSignatureKey());
    }

    /**
     * Checks if post data is valid to be processed.
     *
     * @param array $post
     *
     * @return void
     * @throws BadRequestException
     */
    protected static function checkRequestFields(array $post): void
    {
        foreach (static::REQUEST_FIELDS as $name => $type) {

            if (!isset($post[$name])) {
                throw new BadRequestException("field $name not found in request");
            }

            if (gettype($post[$name]) !== $type) {
                throw new BadRequestException("field $name has incorrect type, $type expected");
            }
        }
    }

    /**
     * Parses payload from request data.
     *
     * @param array  $post
     * @param string $apiToken
     * @param string $signatureKey
     *
     * @return array
     * @throws BadRequestException
     */
    protected static function getVerifiedPayload(array $post, string $apiToken, string $signatureKey): array
    {
        static::checkPayloadSign($post["payload"], $apiToken, $signatureKey, $post["signature"]);
        return $post["payload"];
    }

    /**
     * Makes signature for payload and compares it with received one.
     *
     * @param array  $payload
     * @param string $apiToken
     * @param string $signatureKey
     * @param string $signatureToCompare
     *
     * @return void
     * @throws BadRequestException
     */
    protected static function checkPayloadSign(array $payload, string $apiToken, string $signatureKey, string $signatureToCompare): void
    {
        if (static::getPayloadSign($payload, $apiToken, $signatureKey) !== $signatureToCompare) {

            throw new BadRequestException("payload signature is incorrect");
        }
    }

    # region shared protected

    /**
     * Performs payload sorting before sign.
     *
     * @param array $array
     * @param int   $sort_flags
     *
     * @return bool
     */
    protected static function sortPayload(array &$array, int $sort_flags = SORT_REGULAR): bool
    {
        foreach ($array as &$value) {

            if (is_object($value)) {
                $value = (array)$value;
            }

            if (is_array($value)) {
                static::sortPayload($value);
            }
        }

        return ksort($array, $sort_flags);
    }

    /**
     * @param array  $payload
     * @param string $apiToken
     * @param string $signatureKey
     *
     * @return string
     */
    protected static function getPayloadSign(array $payload, string $apiToken, string $signatureKey): string
    {
        $toSign          = $payload;
        $toSign["token"] = $apiToken;

        // payload must be sorted alphabetically before signing
        static::sortPayload($toSign);

        // get serialized data sign with signature key
        $signature = hash_hmac(static::HASH_ALGO, json_encode($toSign), $signatureKey);

        if ($signature === false) {
            throw new \RuntimeException("can't sign payload");
        }

        return $signature;
    }

    # endregion protected
}
