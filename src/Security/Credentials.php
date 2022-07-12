<?php declare(strict_types=1);

namespace GetCompass\Userbot\Security;

/**
 * Userbot-api credentials.
 * @link https://github.com/getCompass/userbot
 */
class Credentials
{
    /** @var string */
    protected $apiToken;
    /** @var string */
    protected $signatureKey;

    /**
     * Constructor
     *
     * @param string $apiToken
     * @param string $signatureKey
     */
    public function __construct(string $apiToken, string $signatureKey)
    {
        $this->apiToken     = $apiToken;
        $this->signatureKey = $signatureKey;
    }

    /**
     * Returns a compass-api access token.
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    /**
     * Returns a compass-api signature key.
     * @return string
     */
    public function getSignatureKey(): string
    {
        return $this->signatureKey;
    }
}
