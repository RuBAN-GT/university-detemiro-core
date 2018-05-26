<?php
    detemiro::actions()->add(array(
        'function' => function($value) {
            if($value) {
                if($try = \detemiro::pages()->makeBackKey($value)) {
                    return $try;
                }
                else {
                    return '404';
                }
            }

            return $value;
        }
    ));
?>