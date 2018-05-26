# Автоподгрузка

Довольно часто возникает необходимость загружать файлы в определённых ситуациях, 
но для этого надо знать, что они существуют, особенно, если они находятся в различных модулях и они имеют общую цель.

Для выполнения данной задачи в модульной системе реализована система автоподгрузки.

## <a name="realisation"></a>Реализация
Система подгрузки представлена классом [externals](https://docs.detemiro.org/api/classes/detemiro.modules.externals.html), 
и её объект находится в поле [$ext](https://docs.detemiro.org/api/classes/detemiro.html#property_registry) класса *collector*, 
доступно через *detemiro::modules()->ext()*.

## <a name="about"></a>О системе
Данная система представляет для вас всего лишь скайнер нужных файлов и директорий по их именам. 
Финальное действие совершаете вы сами.

Имена (типы) определяются или вручную (через метод *addType*), или автоматически по имени модуля.
В результате сканирования будет доступен список файлов и их вариантов в разных местах системы.

Например, для предопределённого типа *actions* будут просканированы все директории *__externals/actions* в модулях, 
и найденные файлы будут добавлены в общий список.

Т.е. если существуют модули c файлами:

* basic/__externals/actions/zone.system.start.php
* cookie-and-sesion/__externals/actions/zone.system.start.php
* sth/__externals/actions/make.php

То их местоположение будет сохранено в этой системе.