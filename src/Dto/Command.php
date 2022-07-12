<?php declare(strict_types=1);

namespace GetCompass\Userbot\Dto;

/**
 * Command dto.
 */
class Command
{
    /** @var string */
    protected $line;
    /** @var string */
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
     * @param string $senderUserId
     * @param string $messageId
     * @param string $groupId
     */
    public function __construct(string $line, array $arguments, string $senderUserId, string $messageId, string $groupId)
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
     * @return string
     */
    public function getSenderUserId(): string
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