# Сообщения

В Детемиро есть возможность запоминать некоторую строковую информацию, которая затем может выводиться на экран или логироваться.

## <a name="realisation"></a>Реализация
Сообщения для ядра представлены классом [messages](https://docs.detemiro.org/api/classes/detemiro.messages.collector.html), и их объект находится в поле [$messages](https://docs.detemiro.org/api/classes/detemiro.html#property_messages), внутри класса *detemiro*.

Само сообщение тоже является объектом класса [message](https://docs.detemiro.org/api/classes/detemiro.messages.message.html) и хранится внутри *$messages*.

Оно имеет следующие поля:

Поле   | Описание
------ | ------------------------------------------------------
type   | Категория сообщения, стандартно - `system`
status | Подкатегория сообщений, которая хранится в виде числа
text   | Текст сообщения
title  | Заголовок сообщения
date   | Полная дата создания сообщения
save   | Сохранение сообщений в сессии пользователя

Каждое сообщение помимо общей категории `type` имеет свой подкод/статус, который полезен при отладке или нахождению общих сообщений разных категорий.

Статусы предопределены от 1 до 4 и могут быть конвертированы из строк:

Коды            | ID  | Описание
--------------- | --- | ---------------
error, alert    | 1   | Сообщения, содержащие информацию об ошибках.
warning, danger | 2   | Важные, но не критические сообщения.
success, ok     | 3   | Сообщения, содержащие положительный результат.
info, notices   | 4   | Второстепенные сообщения с какой-либо информацией

## <a name="session"></a>Сохранение сообщений
Бывают ситуации, когда необходимо сохранить сообщение при следующем запросе.  
Если доступен механизм сессий, то при установке поля `save` со значением большим 1, сообщения продублируются в сессию и при следующих `save - 1` запросах пользователя будут добавлены снова.

## <a name="examples"></a>Примеры использования
Добавим сообщение:

~~~~php
detemiro::messages()->push(array(
    'title' => 'Это работает!',
    'text'  => 'Проверяем сообщения',
    'type'  => 'custom'
));
~~~~

А теперь получим сообщения статусов *error, warning, info, success* и выведем в twitter-bootstrap разметке:

~~~~php
$res = detemiro::messages()->get(null, array(
    'error', 'warning', 'success', 'info'
));

$class = array(
    1 => 'alert-danger',
    2 => 'alert-warning',
    3 => 'alert-success',
    4 => 'alert-info'
);

$out = '';

if($res) foreach($res as $type=>$block) {
    foreach($block as $item) {
        $out .= '<div class="alert ' . $class[$item->status] .' alert-dismissible" role="alert">';
        $out .= '<button type="button" class="close" data-dismiss="alert" aria-label="Закрыть"><span aria-hidden="true"><i class="md md-close"></i></span></button>';
        $out .= "<h4>{$item->title}</h4>";
        $out .= "<p>{$item->text}</p>";
        $out .= '</div>';
    }
}

echo $out;
~~~~