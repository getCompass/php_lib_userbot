<?php declare(strict_types=1);

namespace GetCompass\Userbot\Exception\Request;

/**
 * Default compass userbot-api request exception class.
 * Any outgoing request logic exceptions must extend it.
 */
abstract class AbstractRequestException extends \GetCompass\Userbot\Exception\AbstractException
{
    /** @var int[] bound error codes */
    protected const BOUND_CODES = [];

    /**
     * Decides is error code bound to exception.
     *
     * @param int $code
     * @return bool
     */
    public static function isMyCode(int $code): bool
    {
        return in_array($code, static::BOUND_CODES, true);
    }
}
