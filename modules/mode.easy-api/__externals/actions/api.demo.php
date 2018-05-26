<?php
    detemiro::actions()->add(array(
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