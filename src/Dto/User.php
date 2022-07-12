<?php declare(strict_types=1);

namespace GetCompass\Userbot\Dto;

/**
 * User Dto.
 */
class User
{
    /** @var string */
    protected $userId;
    /** @var string */
    protected $userName;
    /** @var string */
    protected $avatarFileUrl;

    /**
     * User constructor.
     *
     * @param string $userId
     * @param string $userName
     * @param string $avatarFileUrl
     */
    public function __construct(string $userId, string $userName, string $avatarFileUrl)
    {
        $this->userId        = $userId;
        $this->userName      = $userName;
        $this->avatarFileUrl = $avatarFileUrl;
    }

    /**
     * Return user id. Id is a unique field.
     *
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Return user name. Name isn't a unique field.
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * Return user's avatar file id.
     *
     * @return string
     */
    public function getAvatarFileUrl(): string
    {
        return $this->avatarFileUrl;
    }
}
