<?php
    detemiro::actions()->add(array(
        'function' => function() {
            if($db = detemiro::db()) {
                /**
                 * Опции
                 */
                detemiro::services()->set('options', function() use ($db) {
                    return new detemiro\modules\basic\options($db);
                }, false, true);

                /**
                 * Пользовательские поля
                 */
                detemiro::services()->set('usersProps', function() use ($db) {
                    return new detemiro\modules\basic\usersProps($db);
                }, false, true);

                /**
                 * Права
                 */
                $rules = new detemiro\modules\basic\rules($db);
                detemiro::services()->set('rules', $rules, true);

                /**
                 * Группы
                 */
                $groups = new detemiro\modules\basic\groups($db, $rules);
                detemiro::services()->set('groups', $groups, true);

                /**
                 * Пользователи
                 */
                $users = new detemiro\modules\basic\users($db, $rules, $groups);
                detemiro::services()->set('users', $users, true);

                /**
                 * Текущий пользователь
                 */
                detemiro::services()->set('user', new detemiro\modules\basic\user($db, $users, $groups), false);
                detemiro::services()->set('user.props', function() {
                    return new detemiro\modules\basic\userOwnProps();
                }, false, true);

                return true;
            }
            else {
                detemiro::messages()->push(array(
                    'title'  => "Модуль 'basic' не может работать корректно",
                    'text'   => 'Неверный объект базы данных.',
                    'type'   => 'system',
                    'status' => 'error'
                ));

                return false;
            }
        }
    ));
?>