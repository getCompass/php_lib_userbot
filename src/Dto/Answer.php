<?php declare(strict_types=1);

namespace GetCompass\Userbot\Dto;

/**
 * Answer dto.
 */
class Answer
{
    /** @var string */
    protected $action;
    /** @var array */
    protected $post;

    /**
     * Answer constructor.
     *
     * @param string $action
     * @param array  $post
     */
    public function __construct(string $action, array $post)
    {
        $this->action = $action;
        $this->post   = $post;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getPost(): array
    {
        return $this->post;
    }
}