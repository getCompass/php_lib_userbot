<?php declare(strict_types=1);

namespace GetCompass\Userbot\Command;

use GetCompass\Userbot\Bot;
use GetCompass\Userbot\Dto\Command;

/**
 * Simple Compass userbot command implementation.
 */
class SimpleCommand implements InterfaceCommandHandler
{
    /** @var string registered command with arguments placeholders like /show order [order_id] */
    protected $rawCommand;
    /** @var callable */
    protected $fn;

    /**
     * SimpleCommand constructor.
     *
     * @param string   $rawCommand registered command with arguments placeholders like /show order [order_id]
     * @param callable $fn (\CompassApp\Userbot\Bot $bot, \CompassApp\Userbot\Dto\Command $command): mixed
     */
    public function __construct(string $rawCommand, callable $fn)
    {
        $this->rawCommand = $rawCommand;
        $this->fn         = $fn;
    }

    /**
     * @inheritDoc
     */
    public function run(Bot $bot, Command $command)
    {
        return call_user_func($this->fn, $bot, $command);
    }

    /**
     * @inheritDoc
     */
    public function tryParseCommand(string $command)
    {
        [$commandPrefix] = explode(" ", $command, 2);
        [$handlerPrefix] = explode(" ", $this->rawCommand, 2);

        // do a quick prefix check to avoid heavy regexp parsing
        if ($commandPrefix !== $handlerPrefix) {
            return false;
        }

        // ide decides regexp is broken...
        $bracerRegexp = sprintf("/%s/", "(\[((?>[^\[\]]+)|(?-2))*])");

        $commandMatches = [];
        $lineRegexp     = preg_match_all($bracerRegexp, $this->rawCommand, $commandMatches)
            ? static::makeActionWithArgsRegexp($command, $commandMatches[0])
            : static::makeActionRegexp($this->rawCommand);

        $lineMatches = [];
        if (!preg_match($lineRegexp, $command, $lineMatches)) {
            return false;
        }

        $lineMatches = array_filter($lineMatches, static function (int $k) {
            return $k !== 0 && $k % 2 !== 0;
        }, ARRAY_FILTER_USE_KEY);

        // it's impossible, but why not
        if (count($commandMatches[0]) !== count($lineMatches)) {
            return false;
        }

        return array_combine(static::removeBrackets($commandMatches[0]), static::removeBrackets($lineMatches));
    }

    /**
     * Create regex to validate command and parse it's argument list.
     *
     * @param string $raw
     * @param array  $parts
     *
     * @return string
     */
    protected static function makeActionWithArgsRegexp(string $raw, array $parts): string
    {
        // ide decides regexp is broken...
        $bracerRegexp = sprintf("/%s/", "(\[((?>[^\[\]]+)|(?-2))*])");
        $exploded     = preg_split($bracerRegexp, $raw);

        $result = preg_quote($exploded[0], "/");
        foreach ($parts as $index => $line) {

            $result .= "(\[((?>[^\[\]]+)|(?-2))*])";
            $result .= preg_quote($exploded[$index + 1], "/");
        }

        return "/^$result$/";
    }

    /**
     * Create simple regex to validate command.
     *
     * @param string $raw
     * @return string
     */
    protected static function makeActionRegexp(string $raw): string
    {
        $raw = preg_quote($raw, "/");
        return "/^$raw$/";
    }

    /**
     * Removes brackets from string array.
     *
     * @param string[] $arr
     * @return string[]
     */
    protected static function removeBrackets(array $arr): array
    {
        return array_map(static function (string $element) {

            return preg_replace("#(^\[|]$)#", "", $element);
        }, $arr);
    }
}
