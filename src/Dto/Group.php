<?php declare(strict_types=1);

namespace GetCompass\Userbot\Dto;

/**
 * Group Dto.
 */
class Group
{
    /** @var string */
    protected $id;
    /** @var string */
    protected $name;
    /** @var string */
    protected $avatarFileUrl;

    /**
     * Group constructor.
     *
     * @param string $id
     * @param string $name
     * @param string $avatarFileUrl
     */
    public function __construct(string $id, string $name, string $avatarFileUrl)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->avatarFileUrl = $avatarFileUrl;
    }

    /**
     * Return group id. Id is a unique field.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Return group name. Name is not a unique field.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return group avatar file id.
     *
     * @return string
     */
    public function getAvatarFileUrl(): string
    {
        return $this->avatarFileUrl;
    }
}
