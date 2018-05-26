<?php
    /**
     * Запуск роутера
     */
    detemiro::actions()->add(array(
        'code'     => 'router-detect-start',
        'priority' => -100,
        'function' => function() {
            detemiro::router()->detect();

            detemiro::services()->lock('router');
        }
    ));
?>