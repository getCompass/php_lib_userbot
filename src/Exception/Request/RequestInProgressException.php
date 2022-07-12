<?php declare(strict_types=1);

namespace GetCompass\Userbot\Exception\Request;

/**
 * Request is not ready yet.
 */
class RequestInProgressException extends AbstractRequestException
{
    /** @var int[] bound error codes */
    protected const BOUND_CODES = [7];
}
