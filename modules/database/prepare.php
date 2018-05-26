<?php
    detemiro::actions()->add(array(
        'function' => function() {
            if($cfg = \detemiro::config()->getByPrefix('database.', true)) {
                try {
                    $db = new detemiro\modules\database\dbActions($cfg);
                }
                catch(Exception $error) {
                    detemiro::messages()->push(array(
                        'title'  => 'Ошибка БД',
                        'type'   => 'system',
                        'status' => 'error',
                        'text'   => "При соединения к БД произошла ошибка [{$error->getMessage()}]."
                    ));

                    return false;
                }

                return detemiro::services()->set('db', $db);
            }
        }
    ));
?>