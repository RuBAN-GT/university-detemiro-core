<?php
    detemiro::actions()->add(array(
        'function' => function() {
            $db     = detemiro::db();
            $rules  = detemiro::rules();
            $groups = detemiro::groups();

            detemiro::services()->unLock('users');

            $users = new detemiro\modules\loginAndPass\users($db, $rules, $groups, true);

            detemiro::services()->set('users', $users, true);
            detemiro::services()->set('user', new detemiro\modules\loginAndPass\user($db, $users, $groups), false);
        }
    ));
?>