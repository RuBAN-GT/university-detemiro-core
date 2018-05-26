# Зависимости

В Детемиро реализован механизм зависимостей. 
Ваш проект, его модули могут требовать для своего запуска другие модули, предлагать их использовать или требовать их отключение.

## <a name="realisation"></a>Реализация
Модули для ядра представлены классом [relationsCollector](https://docs.detemiro.org/api/classes/detemiro.modules.relationsCollector.html), 
и их объект находится в поле [$relations](https://docs.detemiro.org/api/classes/detemiro.modules.collector.html#property_relations), внутри класса *\detemiro\modules\collector*.

Сама же зависимость представлена объектом класса [relation](https://docs.detemiro.org/api/classes/detemiro.modules.relation.html) и находится внутри *detemiro::modules()->relations()*.

## <a name="how2do"></a>Описание
Инструкции зависимостей можно указывать в [Конфигурации](Config), [Пространстве](Space) и в [самых модулях](Modules#module), используя ключ `rels`.  
Внутри него необходимо указать массив с ассоциативными массивами (объектами), содержащие два поля: *method, name*. 
Поле `name` содержим код модуля.

Ключ `method` имеет три значения:

* require - модуль из *name* должен быть активирован, Детемиро попытается его активировать при наличии этого модуля. 
* support - модуль из *name* может быть активирован при его наличии.
* avoid - модуль из *name* должен быть максимум определён (т.е. его статус должен быть меньше 1).

## <a name="examples"></a>Пример

~~~~json
"rels"   : [
    { "method": "require", "name": "database" },
    { "method": "support", "name": "xcache" }
]
~~~~

Данный проект или модуль будет обязательно требовать модуль *database* и если модуль *xcache* установлен, то он будет активирован, но это никак не повлияет на запуск исходной структуры.