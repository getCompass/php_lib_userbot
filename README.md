[![en](https://img.shields.io/badge/lang-en-green.svg)](https://github.com/getCompass/php_lib_userbot/blob/master/README.md)
[![ru](https://img.shields.io/badge/lang-ru-green.svg)](https://github.com/getCompass/php_lib_userbot/blob/master/README_ru.md)

# Library for working with the Compass app bot API
This library provides a set of tools for working with bots in the Compass messaging app. [More information about the bot API](https://github.com/getCompass/userbot).
## Creating a class instance for a bot
To configure bot access, you need to get an authorization token inside the app.
```php
// the previously received authorization token are indicated as your-token-here
$bot = new \GetCompass\Userbot\Bot("your-token-here");
```

### For the on-premise version
After creating the instance, you should specify the address of your installed Compass server
by adding a call to the setEndpoint() method:
```php
// replace your-domain.com with your actual domain
$bot->setEndpoint("https://your-domain.com/userbot");
```
This step is required if you are using the on-premise version of the application.
Without specifying setEndpoint(), the library will use saas API by default.

## Using the main userbot API methods
The main methods include:
- sending text messages
- sending files
- adding a reaction to a message

### Sending text messages
Sending messages is available for both private and group chats. It is also possible to add comments to other messages.
```php
// sending a private message to user 160001
$bot->sendPrivateMessage(160001, "Hi User @User-160001 :blush:");

// sending a message to a group
$bot->sendGroupMessage("icVBoMAA36njACiWxy...", "Hi Group Members :blush:");

// sending a comment to a message
$bot->sendMessageToMessageThread("ifghWeffbdDDsdxy...", "Greetings to all participants in the discussion :blush:");
```
### Sending files
Sending files to chats and comments is available via the userbot API. Only one file is allowed to be uploaded at a time, each file is a separate message.
```php
// sending a file to a private chat
$bot->sendFileToPrivate(160001, "/my/awesome/file.png");

// sending a message to a group chat
$bot->sendFileToGroup("icVBoMAA36njACiWxy...", "/my/awesome/file.png");

// sending a file in a comment to a message
$bot->sendFileToMessageThread("ifghWeffbdDDsdxy...", "/my/awesome/file.png");
```
### Adding reactions to messages
In Compass each user can add a reaction to a message. For example, users add them to confirm that a message has been read and the information it contains has been approved. The userbot API supports adding and removing reactions.
```php
// add the specified reaction to a message
$bot->reactOnMessage("ifghWeffbdDDsdxy...", ":blush:");

// remove the specified reaction from the message
$bot->removeReactionFromMessage("ifghWeffbdDDsdxy...", ":blush:");
```

## Servicing Webhook requests
The Compass app will send a webhook request every time a message strictly corresponding to the command line is sent to the chat.
## Adding commands
Any line starting with the `/` character can be used as a command. A command can also have arguments that need to be wrapped in `[]`. Named arguments will then be available inside the handler. Examples of line commands:
* `/hi`
* `/set the timer [timer]`
* `/wheel of fortune [lucky_guys] [prize]`

```php
// announcing the expected commands
// commands must be pre-announced outside the main logic of webhook processing
$bot = new \GetCompass\Userbot\Bot("your-token-here");
$bot->addCommands("/wheel of fortune [lucky_guys] [prize]");
```
### Processing webhook requests
To process requests, you need to create a bot and specify a set of command handlers for it. Each command handler should implement the interface from the set of commands `GetCompass\Userbot\Command\InterfaceCommandHandler interface`. The `GetCompass\Userbot\Command\SimpleCommand` is used as an example.

```php
// initialize the bot instance specifying the handler for the command "/wheel of fortune [lucky_guys] [prize]"
$bot = new \GetCompass\Userbot\Bot(
      "your-token-here",
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

// transmitting the data that came from the server to the handler
$bot->serveWebhook($postData);
```
