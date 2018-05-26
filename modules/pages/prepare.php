<?php
    detemiro::actions()->add(array(
        'function' => function() {
            return detemiro::services()->set('pages', function() {
                return new detemiro\modules\pages\pages();
            }, false, true);
        }
    ));
?>