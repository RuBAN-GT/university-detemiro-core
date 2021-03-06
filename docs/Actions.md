﻿# Экшены

Экшены для Детемиро - это события, вызываемые напрямую или через зону событий (хук). 
Под событиями же подразумевается функция, имеющая внешний код для вызова и дополнительные условия вызова, например, [пространство](Space), в котором событие работает.

Благодаря такой особенности, можно легко корректировать поведение системы в различных её участках.

## <a name="realisation"></a>Реализация
Экшены для ядра представлены классом [actions](https://docs.detemiro.org/api/classes/detemiro.events.actions.html), и их объект находится в поле [$actions](https://docs.detemiro.org/api/classes/detemiro.html#property_actions), внутри класса *detemiro*.

Сам объект экшена представлен класссом [action](https://docs.detemiro.org/api/classes/detemiro.events.action.html).

> Разместив наследник класса *actions* в сервисах под ключём *actions*, будет вызван именно этот наследник.

## <a name="autoload"></a>Подгрузка экшенов
Система экшенов использует [автоподгрузку](Autoload) php-файлов из директорий с именем *actions*.

* Файлы экшенов должны иметь имя с кодом экшена с раширением php (например, *test.php*).
* Файлы с экшенами зоны должны иметь имя *zone.{имя_зоны}.php* (например, *zone.system.start.php*).

## <a name="examples"></a>Примеры использования
Создадим экшен и вызовём его из зоны

~~~~php
detemiro::actions()->add(array(
    'code'     => 'first',
    'zone'     => 'try',
    'priority' => true,
    'function' => function() {
        echo 'Это работает!';
    }
));

detemiro::actions()->makeZone('try');
//Это работает!
~~~~

При вызове метода *makeZone* будут подгружены и найдены все экшены, в которых указана зона *try*, 
после этого будут вызваны их функции в порядке ключа *priority*.