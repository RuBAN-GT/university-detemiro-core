<?php
    detemiro::actions()->add(array(
        'function' => function() {
            if(detemiro::db()) {
                detemiro::services()->set('fieldsControl', function() {
                    return new \detemiro\modules\usersfields\fieldsControl(detemiro::db());
                }, false, true);

                detemiro::services()->set('usersFields', function() {
                    return new \detemiro\modules\usersfields\usersFields();
                }, false, true);

                detemiro::services()->set('user.fields', function() {
                    return new \detemiro\modules\usersfields\userOwnFields();
                }, false, true);

                return true;
            }

            return false;
        }
    ));
?>