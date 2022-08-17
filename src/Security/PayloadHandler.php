<?php declare(strict_types=1);

namespace GetCompass\Userbot\Security;

use GetCompass\Userbot\Exception\Webhook\BadRequestException;

/**
 * Userbot-api security helper.
 * Signs outgoing payload and validates incoming.
 */
class PayloadHandler
{

    /** @var string hash algo, sha256 is required */
    protected const HASH_ALGO = "SHA256";

    /**
     * Prepares given data for sending to Compass API.
     *
     * @param array       $payload
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function encode(array $payload): string
    {
        return \json_encode($payload, JSON_FORCE_OBJECT);
    }

    /**
     * Decodes incoming request.
     *
     * @param Credentials $credentials
     * @param array       $post
     * @param string      $postSignature
     *
     * @return array
     * @throws BadRequestException
     */
    public static function decode(Credentials $credentials, array $post, string $postSignature): array
    {
        return static::getVerifiedPayload($post, $postSignature, $credentials->getApiToken(), $credentials->getSignatureKey());
    }

    /**
     * Parses payload from request data.
     *
     * @param array  $post
     * @param string $postSignature
     * @param string $apiToken
     * @param string $signatureKey
     *
     * @return array
     * @throws BadRequestException
     */
    protected static function getVerifiedPayload(array $post, string $postSignature, string $apiToken, string $signatureKey): array
    {
        static::checkPayloadSign($post, $apiToken, $signatureKey, $postSignature);
        return $post;
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

    /**
     * Get signature for request.
     *
     * @param array  $payload
     * @param string $apiToken
     * @param string $signatureKey
     *
     * @return string
     */
    public static function getPayloadSign(array $payload, string $apiToken, string $signatureKey): string
    {
        $toSign = \json_encode($payload, JSON_FORCE_OBJECT);
        $toSign = $apiToken . $toSign;

        // get serialized data sign with signature key
        $signature = hash_hmac(static::HASH_ALGO, $toSign, $signatureKey);

        if ($signature === false) {
            throw new \RuntimeException("can't sign payload");
        }

        return $signature;
    }
}
