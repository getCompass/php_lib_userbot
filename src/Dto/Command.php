<?php declare(strict_types=1);

namespace GetCompass\Userbot\Dto;

/**
 * Command dto.
 */
class Command
{
    /** @var string */
    protected $line;
    /** @var int */
    protected $senderUserId;
    /** @var string */
    protected $messageId;
    /** @var string */
    protected $groupId;
    /** @var string[] */
    protected $arguments;

    /**
     * Command constructor.
     *
     * @param string $line
     * @param array  $arguments
     * @param int    $senderUserId
     * @param string $messageId
     * @param string $groupId
     */
    public function __construct(string $line, array $arguments, int $senderUserId, string $messageId, string $groupId)
    {
        $this->line         = $line;
        $this->senderUserId = $senderUserId;
        $this->messageId    = $messageId;
        $this->groupId      = $groupId;
        $this->arguments    = $arguments;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    /**
     * @return string
     */
    public function getLine(): string
    {
        return $this->line;
    }

    /**
     * @return int
     */
    public function getSenderUserId(): int
    {
        return $this->senderUserId;
    }

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }
}