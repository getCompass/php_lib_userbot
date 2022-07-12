<?php declare(strict_types=1);

namespace GetCompass\Userbot\Exception\Request;

/**
 * Request data is incorrect — some fields are missed or url is invalid.
 */
class BadRequestException extends AbstractRequestException
{
    /** @var int[] bound error codes */
    protected const BOUND_CODES = [1, 2, 3, 4, 8];
}
