<?php
    /**
     * Проверочный экшен
     */
    detemiro::actions()->add(array(
        'code'     => 'console-demo',
        'function' => function() {
            echo 'Это работает!' . PHP_EOL;

            if($args = func_get_args()) {
                echo 'Все аргументы экшена: ' . PHP_EOL;

                foreach($args as $key => $item) {
                    echo "[$key]: ";
                    var_dump($item);
                }
            }
        }
    ));
?>