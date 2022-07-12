<?php declare(strict_types=1);

namespace GetCompass\Userbot;

use GetCompass\Userbot\Action\Curl;
use GetCompass\Userbot\Action\Request;
use GetCompass\Userbot\Action\UrlProvider;
use GetCompass\Userbot\Command\InterfaceCommandHandler;
use GetCompass\Userbot\Dto\Command;
use GetCompass\Userbot\Dto\Group;
use GetCompass\Userbot\Dto\User;
use GetCompass\Userbot\Security\Credentials;
use GetCompass\Userbot\Security\PayloadHandler;

/**
 * Compass userbot helper.
 * @link https://github.com/getCompass/userbot
 */
class Bot
{
    protected const GET_USERS_MAX_LIMIT  = 300;
    protected const GET_GROUPS_MAX_LIMIT = 300;

    public const SEND_PRIVATE_MESSAGE_METHOD    = "user/send";
    public const SEND_GROUP_MESSAGE_METHOD      = "group/send";
    public const SEND_THREAD_MESSAGE_METHOD     = "thread/send";
    public const ADD_MESSAGE_REACTION_METHOD    = "message/addReaction";
    public const REMOVE_MESSAGE_REACTION_METHOD = "message/removeReaction";
    public const GET_FILE_UPLOAD_URL_METHOD     = "file/getUrl";
    public const GET_USERS_URL_METHOD           = "user/getList";
    public const GET_GROUPS_URL_METHOD          = "group/getList";
    public const GET_COMMANDS_URL_METHOD        = "command/getList";
    public const UPDATE_COMMANDS_URL_METHOD     = "command/update";

    /** @var Credentials $credentials */
    protected $credentials;
    /** @var InterfaceCommandHandler[] */
    protected $commandHandlers = [];

    /**
     * Bot constructor.
     *
     * @param string                  $apiToken
     * @param string                  $signatureKey
     * @param InterfaceCommandHandler ...$commandHandlers
     */
    public function __construct(string $apiToken, string $signatureKey, InterfaceCommandHandler...$commandHandlers)
    {
        $this->credentials     = new Credentials($apiToken, $signatureKey);
        $this->commandHandlers = $commandHandlers;
    }

    /**
     * Serve webhook commands.
     *
     * @param array                     $post
     *
     * @return mixed
     * @throws Exception\Webhook\BadRequestException
     * @throws Exception\Webhook\BadCommandException
     */
    public function serveWebhook(array $post)
    {
        $data = PayloadHandler::decode($this->credentials, $post);

        foreach ($this->commandHandlers as $handler) {

            $args = $handler->tryParseCommand($data["text"]);
            if ($args === false) {
                continue;
            }

            $command = new Command($data["text"], $args, $data["user_id"], $data["message_id"], $data["group_id"]);
            return $handler->run($this, $command);
        }

        throw new Exception\Webhook\BadCommandException("got unknown command {$data["text"]}");
    }

    /**
     * Return userbot-api request handler.
     *
     * @return Request
     */
    public function makeRequest(): Request
    {
        return new Request(new Curl(), $this->credentials);
    }

    /**
     * Returns all groups the bot is a member of.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return Group[]
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-groupgetlist
     */
    public function getGroups(int $limit = self::GET_GROUPS_MAX_LIMIT, int $offset = 0): array
    {
        $this::assert($limit <= static::GET_USERS_MAX_LIMIT && $limit > 0, "passed incorrect limit");
        $this::assert($offset >= 0, "passed incorrect offset");

        $response = $this->callDefault(static::GET_GROUPS_URL_METHOD, [
            "limit"  => $limit,
            "offset" => $offset,
        ]);

        return array_map(static function (array $element) {

            return new Group(
                $element["group_id"] ?? $element["conversation_key"],
                $element["name"] ?? $element["group_name"],
                $element["avatar_file_url"]
            );
        }, $response["group_list"]);
    }

    /**
     * Return all company members.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return User[]
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-usergetlist
     */
    public function getUsers(int $limit = self::GET_USERS_MAX_LIMIT, int $offset = 0): array
    {
        $this::assert($limit <= static::GET_USERS_MAX_LIMIT && $limit > 0, "passed incorrect limit");
        $this::assert($offset >= 0, "passed incorrect offset");

        $response = $this->callDefault(static::GET_USERS_URL_METHOD, [
            "limit"  => $limit,
            "offset" => $offset,
        ]);

        return array_map(static function (array $element) {

            return new User($element["user_id"], $element["user_name"], $element["avatar_file_url"]);
        }, $response["user_list"]);
    }

    /**
     * Return user info by id.
     *
     * @param string $userId
     * @return User
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     * @throws Exception\Request\UserNotFoundException
     *
     * @link https://github.com/getCompass/userbot#post-usergetlist
     */
    public function getUser(string $userId): User
    {
        $this::assert($userId !== "", "passed empty user id");
        $page = 0;

        do {

            $users = $this->getUsers(static::GET_USERS_MAX_LIMIT, static::GET_USERS_MAX_LIMIT * $page++);

            foreach ($users as $user) {

                if ($user->getUserId() === $userId) {
                    return $user;
                }
            }
        } while (count($users) >= static::GET_USERS_MAX_LIMIT);

        throw new Exception\Request\UserNotFoundException("user $userId not found");
    }

    /**
     * Send a text message to the private conversation.
     *
     * @param string $userId
     * @param string $text
     *
     * @return string sent message_id
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-usersend
     */
    public function sendPrivateMessage(string $userId, string $text): string
    {
        $this::assert($userId !== "", "passed empty user id");
        $this::assert($text !== "", "passed empty message");

        $response = $this->callDefault(static::SEND_PRIVATE_MESSAGE_METHOD, [
            "text"    => $this->preProcessMessageText($text),
            "type"    => "text",
            "user_id" => $userId,
        ]);

        return $response["message_id"];
    }

    /**
     * Send a text message to the group conversation.
     *
     * @param string $groupKey
     * @param string $text
     *
     * @return string
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-groupsend
     */
    public function sendGroupMessage(string $groupKey, string $text): string
    {
        $this::assert($groupKey !== "", "passed empty group id");
        $this::assert($text !== "", "passed empty message");

        $response = $this->callDefault(static::SEND_GROUP_MESSAGE_METHOD, [
            "text"     => $this->preProcessMessageText($text),
            "type"     => "text",
            "group_id" => $groupKey,
        ]);

        return $response["message_id"];
    }

    /**
     * Send a text message to the message thread.
     *
     * @param string $messageKey
     * @param string $text
     *
     * @return string
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-threadsend
     */
    public function sendMessageToMessageThread(string $messageKey, string $text): string
    {
        $this::assert($messageKey !== "", "passed empty message id");
        $this::assert($text !== "", "passed empty message");

        $response = $this->callDefault(static::SEND_THREAD_MESSAGE_METHOD, [
            "text"       => $this->preProcessMessageText($text),
            "type"       => "text",
            "message_id" => $messageKey,
        ]);

        return $response["message_id"];
    }

    /**
     * Send a file message to the private conversation.
     *
     * @param string $userId
     * @param string $filePath
     *
     * @return string
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-usersend
     */
    public function sendFileToPrivate(string $userId, string $filePath): string
    {
        $this::assert($userId !== "", "passed empty user id");

        $uploadInfo = $this->getFileUploadInfo($filePath);
        $response   = $this->callDefault(static::SEND_PRIVATE_MESSAGE_METHOD, [
            "user_id" => $userId,
            "type"    => "file",
            "file_id" => $uploadInfo["file_id"] ?? $uploadInfo["file_key"],
        ]);

        return $response["message_id"];
    }

    /**
     * Send a file message to the group conversation.
     *
     * @param string $groupId
     * @param string $filePath
     *
     * @return string
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-groupsend
     */
    public function sendFileToGroup(string $groupId, string $filePath): string
    {
        $this::assert($groupId !== "", "passed empty group id");

        $uploadInfo = $this->getFileUploadInfo($filePath);
        $response   = $this->callDefault(static::SEND_GROUP_MESSAGE_METHOD, [
            "group_id" => $groupId,
            "type"     => "file",
            "file_id"  => $uploadInfo["file_id"] ?? $uploadInfo["file_key"],
        ]);

        return $response["message_id"];
    }

    /**
     * Send a file message to the message thread.
     *
     * @param string $messageId
     * @param string $filePath
     *
     * @return string
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-threadsend
     */
    public function sendFileToMessageThread(string $messageId, string $filePath): string
    {
        $this::assert($messageId !== "", "passed empty message id");

        $uploadInfo = $this->getFileUploadInfo($filePath);
        $response   = $this->callDefault(static::SEND_THREAD_MESSAGE_METHOD, [
            "message_id" => $messageId,
            "type"       => "file",
            "file_id"    => $uploadInfo["file_id"] ?? $uploadInfo["file_key"],
        ]);

        return $response["message_id"];
    }

    /**
     * Add one reaction to the message.
     * Use utf emoji or emoji short key as reaction value.
     *
     * @param string $messageKey
     * @param string $reaction
     *
     * @return void
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-messageaddreaction
     */
    public function reactOnMessage(string $messageKey, string $reaction): void
    {
        $this::assert($messageKey !== "", "passed empty message id");
        $this::assert($reaction !== "", "passed empty reaction");

        $this->callDefault(static::ADD_MESSAGE_REACTION_METHOD, [
            "message_id" => $messageKey,
            "reaction"   => $reaction,
        ]);
    }

    /**
     * Remove one reaction to the message.
     * Use utf emoji or emoji short key as reaction value.
     *
     * @param string $messageKey
     * @param string $reaction
     *
     * @return void
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-messageremovereaction
     */
    public function removeReactionFromMessage(string $messageKey, string $reaction): void
    {
        $this::assert($messageKey !== "", "passed empty message id");
        $this::assert($reaction !== "", "passed empty reaction");

        $this->callDefault(static::REMOVE_MESSAGE_REACTION_METHOD, [
            "message_id" => $messageKey,
            "reaction"   => $reaction,
        ]);
    }

    /**
     * Get file upload info.
     * Use node url as address and token as payload validation field.
     *
     * @param string $filePath
     *
     * @return array ["node_url" => string, "token" => string]
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-filegeturl
     */
    public function getFileUploadInfo(string $filePath): array
    {
        $this::assert(file_exists($filePath), "file $filePath not found");

        // get upload url
        $response = $this->makeRequest()
            ->withAddress(UrlProvider::apiv1(static::GET_FILE_UPLOAD_URL_METHOD))
            ->send()
            ->waitResponse();

        return $this->makeRequest()
            ->withSign(false)
            ->withAddress($response["node_url"])
            ->withMessage(["token" => $response["file_token"]])
            ->withFile($filePath)
            ->send()
            ->waitResponse();
    }

    /**
     * Returns current commands served by webhook.
     *
     * @return string[]
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-commandgetlist
     */
    public function getCommands(): array
    {
        $response = $this->callDefault(static::GET_COMMANDS_URL_METHOD, []);
        return $response["command_list"];
    }

    /**
     * Returns current commands served by webhook.
     *
     * @
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-commandupdate
     */
    public function addCommands(string...$commands): void
    {
        $this::assert(count($commands) > 0, "no commands passed");

        foreach ($commands as $command) {
            $this::assert($command !== "", "passed incorrect command '$command'");
        }

        // get existing commands, we don't want to lose them
        $response = $this->callDefault(static::GET_COMMANDS_URL_METHOD, []);

        $this->callDefault(static::UPDATE_COMMANDS_URL_METHOD, [
            "command_list" => array_unique(array_merge($response["command_list"], $commands))
        ]);
    }

    /**
     * Returns specified command from webhook.
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-commandupdate
     */
    public function removeCommand(string $command): void
    {
        $this::assert($command !== "", "passed incorrect command '$command'");

        // get existing commands, we don't want to lose them
        $response = $this->callDefault(static::GET_COMMANDS_URL_METHOD, []);

        // looking for existing command
        $key = array_search($command, $response["command_list"], true);
        if ($key === false) {
            return;
        }

        unset($response["command_list"][$key]);
        $this->callDefault(static::UPDATE_COMMANDS_URL_METHOD, [
            "command_list" => $response["command_list"]
        ]);
    }

    /**
     * Remove any command from webhook.
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     *
     * @link https://github.com/getCompass/userbot#post-commandupdate
     */
    public function clearCommands(): void
    {
        $this->callDefault(static::UPDATE_COMMANDS_URL_METHOD, [
            "command_list" => []
        ]);
    }

    # region shared protected

    /**
     * Prepare special markdown before sending.
     * Replace User-12345 syntax by correct mention.
     *
     * @param string $text
     * @return string
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     */
    protected function preProcessMessageText(string $text): string
    {
        $matches = [];
        if (!preg_match_all("/(@User-\d+)/", $text, $matches)) {
            return $text;
        }

        $searchFor = array_flip($matches[0]);
        $usersInfo = [];
        $page      = 0;

        do {

            $users = $this->getUsers(static::GET_USERS_MAX_LIMIT, static::GET_USERS_MAX_LIMIT * $page++);
            foreach ($users as $user) {

                if (isset($searchFor["@{$user->getUserId()}"])) {

                    $usersInfo["@{$user->getUserId()}"] = sprintf(
                        "[\"@\"|%s|\"%s\"]",
                        str_replace("User-", "", $user->getUserId()),
                        $user->getUserName()
                    );
                }
            }
        } while (count($users) >= static::GET_USERS_MAX_LIMIT && count($searchFor) !== count($usersInfo));

        return str_replace(array_keys($usersInfo), $usersInfo, $text);
    }

    /**
     * Call userbot-api in default flow — set, send, wait.
     *
     * @param string $method
     * @param array  $payload
     *
     * @return array
     *
     * @throws Exception\Request\BadRequestException
     * @throws Exception\Request\UnexpectedResponseException
     */
    protected function callDefault(string $method, array $payload): array
    {
        return $this->makeRequest()
            ->withAddress(UrlProvider::apiv1($method))
            ->withMessage($payload)
            ->send()
            ->waitResponse();
    }

    /**
     * Assert passed data before request.
     *
     * @param bool   $expression
     * @param string $message
     * @return void
     *
     * @throws Exception\Request\BadRequestException
     */
    protected static function assert(bool $expression, string $message = "passed incorrect value"): void
    {

        if ($expression) {
            return;
        }

        throw new Exception\Request\BadRequestException($message);
    }

    # endregion shared protected
}
