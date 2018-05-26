<?php
    /**
     * Режим Консоль
     *
     * Данный режим позволяет исполнять экшены при вызове из консоли.
     */
    detemiro::actions()->add(array(
        'function' => function() {
            global $argv;

            if($argv) {
                $send = array();

                if(count($argv) > 1 && is_string($argv[1]) && $argv[1]) {
                    $send[] = $argv[1];

                    if(count($argv) > 2) {
                        $copy = array_slice($argv, 2);

                        while($copy) {
                            $send[] = array_shift($copy);
                        }
                    }

                    if($send && $send[0] != 'system.mode.console') {
                        if($try = detemiro::config()->get('console.prefix')) {
                            if(is_string($try)) {
                                $prefix = $try;
                            }
                            else {
                                $prefix = 'console.';
                            }
                        }
                        else {
                            $prefix = '';
                        }

                        $send[0] = $prefix . $send[0];

                        call_user_func_array(array(detemiro::actions(), 'make'), $send);
                    }
                }
            }
        }
    ));
?>