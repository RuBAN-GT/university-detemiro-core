<?php
    detemiro::actions()->set(array(
        'function' => function() {
            try {
                $cache = new detemiro\modules\xcache\xcache(\detemiro::config()->getByPrefix('cache.', true));
            }
            catch(Exception $error) {
                detemiro::messages()->push(array(
                    'title'  => 'Ошибка XCache',
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