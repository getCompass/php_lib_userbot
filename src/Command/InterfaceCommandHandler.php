<?php declare(strict_types=1);

namespace GetCompass\Userbot\Command;

use GetCompass\Userbot\Bot;
use GetCompass\Userbot\Dto\Command;

/**
 * Webhook command.
 */
interface InterfaceCommandHandler
{
    /**
     * Run the command.
     *
     * @param Bot     $bot
     * @param Command $command
     *
     * @return mixed
     */
    public function run(Bot $bot, Command $command);

    /**
     * Decides may be the command be handled by the handler.
     * Returns action arguments on success, false if handler is not valid for command.
     *
     * @return array|false
     */
    public function tryParseCommand(string $command);
}
