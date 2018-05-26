<?php
    /**
     * Функции
     */
    require_once($path . '/includes/functions.php');

    /**
     * Поддельное замыкание
     */
    require_once($path . '/includes/fakeClosure.php');

    /**
     * Трейт одиночки
     */
    require_once($path . '/includes/single.php');

    /**
     * Магический контроль
     */

    require_once($path . '/includes/magicControl.php');

    /**
     * Регистр
     */
    require_once($path . '/includes/registry.php');

    /**
     * Контейнер сервисов (регистр с инициализацией)
     */
    require_once($path . '/includes/serviceContainer.php');

    /**
     * Система сообщений
     */
    require_once($path . '/includes/messages/message.php');
    require_once($path . '/includes/messages/collector.php');

    /**
     * Пространство
     */
    require_once($path . '/includes/space.php');

    /**
     * Коллекторы событий
     */
    require_once($path . '/includes/events/object.php');
    require_once($path . '/includes/events/action.php');

    require_once($path . '/includes/events/collector.php');
    require_once($path . '/includes/events/actions.php');

    /**
     * Модули
     */
    require_once($path . '/includes/modules/relationsCollector.php');
    require_once($path . '/includes/modules/relation.php');
    require_once($path . '/includes/modules/iStatuses.php');
    require_once($path . '/includes/modules/memoryStatuses.php');
    require_once($path . '/includes/modules/jsonStatuses.php');
    require_once($path . '/includes/modules/module.php');
    require_once($path . '/includes/modules/externals.php');
    require_once($path . '/includes/modules/collector.php');

    /**
     * Главный класс
     */
    require_once($path . '/includes/detemiro.php');
?>