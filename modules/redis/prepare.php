<?php
    /**
     * Инициализация объекта для работы с кешем
     */
    detemiro::actions()->set(array(
        'function' => function() {
            try {
                $cache = new detemiro\modules\redis\redis(\detemiro::config()->getByPrefix('cache.', true));
            }
            catch(Exception $error) {
                detemiro::messages()->push(array(
                    'title'  => 'Ошибка Redis',
                    'type'   => 'system',
                    'status' => 'error',
                    'text'   => $error->getMessage()
                ));

                return false;
            }

            return detemiro::services()->set('cache', $cache, true);
        }
    ));
?>