# Библиотека для работы с API чат-ботов приложения Compass
Библиотека предоставляет набор инструментов для работы с чат-ботами в мессенджере Compass. [Больше информации об API чат-ботов](https://github.com/getCompass/userbot).
## Создание экземпляра класса для бота
Для настройки доступов бота необходимо получить токен авторизации и ключ подписи внутри приложения.
```php
// в качестве your-signature-key-here и your-token-here указываюся ранее полученные токен авторизации и ключ подписи
$bot = new \GetCompass\Userbot\Bot("your-token-here", "you-signature-key-here");
```
## Использование основных методов userbot-api
К основным методам относятся:
- отправка текстовых сообщений;
- отправка файлов;
- добавление реакции на сообщение.

### Отправка текстовых сообщений
Отправка доступна как в личные диалоги, так и в групповые. Также возможно отправлять сообщения-комментарии к другим сообщениям.
```php
// отправка личного сообщения пользователю 160XXX
$bot->sendPrivateMessage("User-160XXX", "Привет, пользователь @User-160XXX :blush:");

// отправка сообщения в группу
$bot->sendGroupMessage("icVBoMAA36njACiWxy...", "Привет, участники группы :blush:");

// отправка комментария к сообщению
$bot->sendMessageToMessageThread("ifghWeffbdDDsdxy...", "Приветствую всех участников обсуждения :blush:");
```
### Отправка сообщений с файлами
Через userbot-api доступна отправка файлов в чаты и комментарии. За один раз разрешается загрузить только один файл, каждый загруженный файл является отдельным сообщением.
```php
// отправка файла в личный диалог
$bot->sendFileToPrivate("User-160XXX", "/my/awesome/file.png");

// отправка файла в группу
$bot->sendFileToGroup("icVBoMAA36njACiWxy...", "/my/awesome/file.png");

// отправка файла в комментарии к сообщению
$bot->sendFileToMessageThread("ifghWeffbdDDsdxy...", "/my/awesome/file.png");
```
### Реакции на сообщения
В приложении Compass каждый пользователь может поставить реакцию на сообщение. С помощью реакции пользователь может подтвердить, что сообщение прочитано, или что изложенная в нем идея одобрена. Userbot-api поддерживает добавление и удаление реакций.
```php
// добавить указанную реакцию к сообщению
$bot->reactOnMessage("ifghWeffbdDDsdxy...", ":blush:");

// убрать указанную реакцию у сообщения
$bot->removeReactionFromMessage("ifghWeffbdDDsdxy...", ":blush:");
```

## Обслуживание запросов на webhook
Приложение Compass будет выполнять запрос на webhook каждый раз, когда в чате появляется сообщение, строго соответствующее строке команды.
### Добавление команд
В качестве команды может использоваться любая строка, начинающаяся с символа `/`. Команда также может иметь аргументы, которые необходимо обернуть в `[]`.
Именованные аргументы далее будут доступны внутри обработчика.
Примеры строковых команд:
* `/привет`
* `/поставь таймер [timer]`
* `/колесо фортуны [lucky_guys] [prize]`

```php
// объявляем ожидаемые команды
// команды нужно предварительно объявить вне основной логики обработки webhook
$bot = new \GetCompass\Userbot\Bot("your-token-here", "your-signature-key-here");
$bot->addCommands("/колесо фортуны [lucky_guys] [prize]");
```
### Обработка webhook-запросов
Для обработки запросов необходимо создать бота, указав ему набор обработчиков команд. Каждый обработчик из набора команд должен реализовывать интерфейс `GetCompass\Userbot\Command\InterfaceCommandHandler`. В качестве примера используется обработчик `GetCompass\Userbot\Command\SimpleCommand`.

```php
// инициализируем экземпляр бота с указанием обработчика для команды "/колесо фортуны [lucky_guys] [prize]"
$bot = new \GetCompass\Userbot\Bot(
    "your-token-here", 
    "your-signature-key-here",
    new \GetCompass\Userbot\Command\SimpleCommand(
        "/колесо фортуны [lucky_guys] [prize]",
        function (\GetCompass\Userbot\Bot $bot, \GetCompass\Userbot\Dto\Command $command) {
            $luckyGuys = explode(" ", $command->getArguments()["lucky_guys"]);
            $winner = $luckyGuys[array_rand($luckyGuys)];
            $prize = $command->getArguments()["prize"];
            $bot->sendGroupMessage($command->getGroupId(), ":crown: и победителем становится — ++$winner++, унося с собой --$prize--");
        }
    )
);

// передаем post-данные, пришедшие с сервера, в обработчик
$bot->serveWebhook($_POST);
```
