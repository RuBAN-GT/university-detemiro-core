<?php
    detemiro::actions()->add(array(
        'function' => function($value) {
            return detemiro::user()->checkGroups($value);
        }
    ));
?>