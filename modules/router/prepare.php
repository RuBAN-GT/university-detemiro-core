<?php
    /**
     * Инициализация объекта роутера
     */
    detemiro::actions()->add(array(
        'function' => function() {
            return detemiro::services()->set('router', function() {
                return new detemiro\modules\router\router(detemiro::config()->getByPrefix('router.', true));
            }, false, true);
        }
    ));
?>