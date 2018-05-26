<?php
    /**
     * Инициализация объекта темы
     */
    detemiro::actions()->add(array(
        'function' => function() {
            //во, эталонное создание
            try {
                $theme = new detemiro\modules\theme\theme(detemiro::config()->theme);

                return detemiro::services()->set('theme', $theme);
            }
            catch(\Exception $error) {
                detemiro::messages()->push(array(
                    'title'  => 'Ошибка темы',
                    'type'   => 'system',
                    'status' => 'error',
                    'text'   => $error->getMessage()
                ));

                return false;
            }
        }
    ));
?>