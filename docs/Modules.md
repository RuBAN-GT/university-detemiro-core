﻿# Модули

Функционал проекта на Детемиро может быть расширен с помощью **Модулей**.
По сути, модули - это произвольные php-скрипты с дополнительной мета-информацией, а также своими [зависимостями](Relations) для успешной работы.

## <a name="realisation"></a>Реализация
Модули для ядра представлены классом [modules](https://docs.detemiro.org/api/classes/detemiro.modules.collector.html), и их объект находится в поле [$modules](https://docs.detemiro.org/api/classes/detemiro.html#property_modules), внутри класса *detemiro*.

Сам же модуль представлен объектом класса [module](https://docs.detemiro.org/api/classes/detemiro.modules.module.html) и находится внутри $modules.

## <a name="statuses"></a>Статусы
Каждый модуль имеет статус: установлен, активирован, определён.

Статус      | Код | Описание
----------- | :-: | ---------------------------------------------------------------------------------
Определён   |  0  | Модуль найден системой и может быть установлен или активирован.
Установлен  |  1  | Модуль был установлен и может быть активирован или быть удалённым.
Активирован |  2  | Модуль был активирован и прошёл установки, его можно деактивировать или удалить.

## <a name="storage"></a>Хранение статусов
Чтобы сохранить статусы Детемиро создаёт файл *det-space/modules.{$код_пространства}.json*, в котором хранятся соответствия статусов модулей и их кодов.

> Для осуществления данной возможности необходимо либо предварительно создать этот файл с возможность записи для пользователя сервера или поставить такие права на саму директорию *det-space*.

## <a name="how2do"></a>Определение
Аналогично [определению конфигурации](Config#priority) система модулей может использовать модули родительских проектов, локальные модули, а также модули ядра, которые находятся в директори *modules*, внутри ядра.

Локальные и родительские модули должны находится в директории *det-content/modules*.

Всё это сканирование происходит во время успешного вызова метода [main](https://docs.detemiro.org/api/classes/detemiro.html#method_main) класса *detemiro*.

После проверки отношений в методе [run](https://docs.detemiro.org/api/classes/detemiro.html#method_run) происходит запуск активированных модулей.

> Во вреия запуска модулей происходит их сканирование на наличие [подгружаемый событий](#autoload).

## <a name="classload"></a>Подгрузка классов
Благодаря [SPL](http://php.net/manual/ru/book.spl.php) Детемиро подгружает классы, опираясь на базу модулей, по следующей схеме:

1. Если класс `test` принадлежит пространству имён `detemiro\modules\trying`, то в системе модулей будет происходить поиск модуля `trying`.
2. Если он будет найден, то подгрузится файл *\_\_includes/test.php*, если тот существует.

## <a name="autoload"></a>Подгрузка
Система модулей помимо классов способна [подгружать](Autoload) различные файлы из директории *\_\_externals*, которая должна находиться внутри модуля.

## <a name="module"></a>Определение модуля
Для того, чтобы система модулей считала папку модулем, необходимо, 
чтобы она находилась или в директории *modules*, внутри ядра, или в *det-content/modules*. 
Имя папки модуля будет кодом транслированным в латиницу, если противное не будет указано в *info.json*. 
Также в этом файле указывается дополнительная мета-информация о модулей: авторы, ссылки, описание и т.п, и [отношения](Relations), т.е. другие модули, необходимые для данного.

Всё это описание представляется в виде JSON-объекта, например:

~~~~json
{
    "code"    : "loginAndPass",
    "version" : "0.9",
    "info"    : "Модуль, добавляет логин и пароли для пользователей.",
    "author"  : "DetemiroTM",
    "rels"    : [
        {
            "method": "require",
            "name"  : "basic"
        }
    ]
}
~~~~

Модуль `loginAndPass` для своего запуска требует модуль `basic`.

## <a name="statuses"></a>Статусы модулей
Модульная система может определять статусы и сохранять их в JSON-файле. 
Эту особенность можно отключить, указав в конфигурации ключ `memory` со значением **true**.

Также хранилище статусов можно изменить на собственное, если между вызовами методов *main* и *run*, 
добавить сервис `modulesStatuses`, содержащий объект, который является наследником интерфейса [iStatuses](https://docs.detemiro.org/api/classes/detemiro.modules.iStatuses.html).