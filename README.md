[![en](https://img.shields.io/badge/lang-en-green.svg)](https://github.com/getCompass/php_lib_userbot/blob/master/README.md)
[![ru](https://img.shields.io/badge/lang-ru-green.svg)](https://github.com/getCompass/php_lib_userbot/blob/master/README_ru.md)

# A library for working with the Compass app chatbot API
This library provides a set of tools for working with chatbots in the Compass messaging app. More information about the chatbot API.
## Creating a class instance for a bot
To configure bot access, you need to get an authorization token and a signature key inside the app.
```php
// the previously received authorization token and signature key are indicated as your-signature-key-here and your-token-here
$bot = new \GetCompass\Userbot\Bot("your-token-here", "you-signature-key-here");
```
## Using the main userbot-api methods
The main methods include:
- sending text messages;
- sending files;
- adding a reaction to a message.

### Sending text messages
Sending messages is available both in private chats and in group chats. It is also possible to send comments to other messages.
```php
// sending a private message to user 160001
$bot->sendPrivateMessage(160001, "Hi User @User-160001 :blush:");

// sending a message to a group
$bot->sendGroupMessage("icVBoMAA36njACiWxy...", "Hi Group Members :blush:");

// sending a comment to a message
$bot->sendMessageToMessageThread("ifghWeffbdDDsdxy...", "Greetings to all participants in the discussion :blush:");
```
### Sending messages with files
Sending files to chats and comments is available via the userbot-api. Only one file is allowed to be uploaded at a time, each uploaded file is a separate message.
```php
// sending a file to a private chat
$bot->sendFileToPrivate(160001, "/my/awesome/file.png");

// sending a message to a group chat
$bot->sendFileToGroup("icVBoMAA36njACiWxy...", "/my/awesome/file.png");

// sending a file in a comment to a message
$bot->sendFileToMessageThread("ifghWeffbdDDsdxy...", "/my/awesome/file.png");
```
### Reactions to messages
In the Compass app, each user can add a reaction to a message. With the reaction, the user can confirm that the message has been read or that an idea is approved. The userbot-api supports adding and removing reactions.
```php
// add the specified reaction to a message
$bot->reactOnMessage("ifghWeffbdDDsdxy...", ":blush:");

// remove the specified reaction from the message
$bot->removeReactionFromMessage("ifghWeffbdDDsdxy...", ":blush:");
```

## Webhook Request Service
The Compass app will execute a webhook request every time a message strictly corresponding to the command line is sent in the chat.
## Adding commands
Any line starting with the / character can be used as a command. A command can also have arguments that need to be wrapped in [].
Named arguments will then be available inside the handler.
Examples of line commands:
* `/hi`
* `/set the timer [timer]`
* `/wheel of fortune [lucky_guys] [prize]`

```php
// announcing the expected commands
// commands must be pre-announced outside the main logic of webhook processing
$bot = new \GetCompass\Userbot\Bot("your-token-here", "your-signature-key-here");
$bot->addCommands("/wheel of fortune [lucky_guys] [prize]");
```
### Processing webhook requests
To process requests, you need to create a bot by specifying a set of command handlers for it. Each command handler from the set of commands must implement the interface GetCompass\Usbot\Command\InterfaceCommandHandler interface. The GetCompass\Usbot\Command\SimpleCommand handler is used as an example.

```php
// initialize the bot instance specifying the handler for the command "/wheel of fortune [lucky_guys] [prize]"
$bot = new \GetCompass\Userbot\Bot(
      "your-token-here",
      "your-signature-key-here",
      new \GetCompass\Userbot\Command\SimpleCommand(
            "/wheel of fortune [lucky_guys] [prize]",
            function (\GetCompass\Userbot\Bot $bot, \GetCompass\Userbot\Dto\Command $command) {
                  $luckyGuys = explode(" ", $command->getArguments()["lucky_guys"]);
                  $winner = $luckyGuys[array_rand($luckyGuys)];
                  $prize = $command->getArguments()["prize"];
                  $bot->sendGroupMessage($command->getGroupId(), ":crown: and the winner is — ++$winner++, taking with them --$prize--");
            }
      )
);

// getting the data sent to the bot
$postData = file_get_contents("php://input");
$postData = json_decode($postData, true);

// getting the signature of the request for data validation
$postSignature = $_SERVER["HTTP_SIGNATURE"] ?? "";
$postSignature = $bot->getHeaderPostSignature($postSignature);

// transmitting the data that came from the server to the handler
$bot->serveWebhook($postData, $postSignature);
```