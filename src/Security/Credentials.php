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

    /**
     * Constructor
     *
     * @param string $apiToken
     */
    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Returns a compass-api access token.
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }
}
