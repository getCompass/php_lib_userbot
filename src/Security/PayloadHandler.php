<?php declare(strict_types=1);

namespace GetCompass\Userbot\Security;

/**
 * Userbot-api security helper.
 * Signs outgoing payload and validates incoming.
 */
class PayloadHandler
{

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
        return \json_encode($payload, JSON_FORCE_OBJECT | JSON_INVALID_UTF8_IGNORE);
    }
}
