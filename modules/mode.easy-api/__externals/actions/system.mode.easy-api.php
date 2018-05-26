<?php
    detemiro::actions()->add(array(
        'function' => function() {
            /**
             * Определение источника данных
             */
            if($main = file_get_contents('php://input')) {
                parse_str($main, $main);
            }
            else {
                $main = array();
            }

            if($_GET) {
                $main = array_merge($main, $_GET);
            }

            if($main && isset($main['action']) && $main['action'] != 'system.mode.easy-api') {
                /**
                 * Получение аргументов для экшена
                 */
                if(array_key_exists('params', $main)) {
                    if($try = \detemiro\json_decode_struct($main['params'])) {
                        $main['params'] = $try;
                    }

                    if($main['params'] && is_array($main['params']) == false) {
                        $main['params'] = array($main['params']);
                    }
                }

                detemiro::registry()->set('mode.easy-api.input', $main, true);

                /**
                 * Выбор префикса для кода экшенов
                 */
                if($try = detemiro::config()->get('easy-api.prefix')) {
                    if(is_string($try)) {
                        $prefix = $try;
                    }
                    else {
                        $prefix = 'api.';
                    }
                }
                else {
                    $prefix = '';
                }

                $args = (isset($main['params']) && is_array($main['params'])) ? $main['params'] : null;

                $args = ($args) ? array_merge(array($prefix . $main['action']), $args) : array($prefix . $main['action']);

                /**
                 * Вызов экшена
                 */
                call_user_func_array(array(detemiro::actions(), 'make'), $args);
            }
            else {
                return null;
            }
        }
    ));
?>