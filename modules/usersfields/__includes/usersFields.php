<?php
    namespace detemiro\modules\usersfields;

    /**
     * Класс-обёртка для установки полей пользователей
     *
     * @services usersFields
     */
    class usersFields {
        /**
         * Установка значения поля для пользователя
         *
         * @zones usersfields.change.{$field_type},usersfields.change.{$field_type}.{$name}
         *
         * @param  int    $id
         * @param  string $name
         * @param  mixed  $value
         * @param  bool   $handlers Использование промежуточных обработчиков
         *
         * @return bool
         */
        public function set($id, $name, $value, $handlers = true) {
            if($handlers) {
                //Обработка значения
                $this->changeValue($name, $value);

                //Проверка
                if($this->checkValue($name, $value) === false) {
                    return false;
                }
            }

            return \detemiro::usersProps()->set(array(
                'user_id' => $id,
                'family'  => 'usersfields',
                'code'    => $name
            ), $value);
        }

        /**
         * Получение всех полей пользователя
         *
         * @param  int $id
         *
         * @return array|null
         */
        public function all($id) {
            return \detemiro::usersProps()->getUserFamily($id, 'usersfields');
        }

        /**
         * Получение значения поля пользователя
         *
         * @actions usersfields.get.{$field_type}.{$name},usersfields.get.{$field_type}
         *
         * @param int    $id
         * @param string $name
         *
         * @return array|null
         */
        public function get($id, $name) {
            $res = \detemiro::usersProps()->getValue(array(
                'user_id' => $id,
                'family'  => 'usersfields',
                'code'    => $name
            ));

            if($field = \detemiro::fieldsControl()->getItem($name)) {
                if($action = \detemiro::actions()->get("usersfields.get.{$field['type']}.{$field['name']}")) {
                    $res['value'] = $action->make($res['value'], $res);
                }
                elseif($action = \detemiro::actions()->get("usersfields.get.{$field['type']}.{$field['name']}")) {
                    $res['value'] = $action->make($res['value'], $res);
                }

                return $res;
            }
            else {
                return null;
            }
        }

        /**
         * Проверка существования поля у пользователя
         *
         * @param  int    $id
         * @param  string $name
         *
         * @return bool
         */
        public function exists($id, $name) {
            return (\detemiro::usersProps()->exists(array(
                'user_id' => $id,
                'family'  => 'usersfields',
                'code'    => $name
            )));
        }

        /**
         * Удаления поля пользователя
         *
         * @param  int    $id
         * @param  string $name
         *
         * @return bool
         */
        public function delete($id, $name) {
            return (\detemiro::usersProps()->deleteItem(array(
                'user_id' => $id,
                'family'  => 'usersfields',
                'code'    => $name
            )));
        }

        /**
         * Удаление поля у всех пользователей
         *
         * @param  string $name
         *
         * @return bool
         */
        public function deleteField($name) {
            return (\detemiro::usersProps()->delete(array(
                array(
                    'param' => 'family',
                    'value' => 'usersfields'
                ),
                array(
                    'param' => 'code',
                    'value' => $name
                )
            )));
        }

        /**
         * Смена имени поля
         *
         * @param  string $old
         * @param  string $new
         *
         * @return bool
         */
        public function renameField($old, $new) {
            \detemiro::fieldsControl()->__change_name($new);

            return (
                \detemiro::fieldsControl()->__check_name($new) &&
                \detemiro::usersProps()->update(
                    array(
                        'code' => $new
                    ),
                    array(
                        array(
                            'param' => 'code',
                            'value' => $old
                        ),
                        array(
                            'param' => 'family',
                            'value' => 'usersfields'
                        )
                    )
                )
            );
        }

        /**
         * Преобразования значения для поля
         *
         * @zones usersfields.change.{$field_type},usersfields.change.{$field_type}.{$name}
         *
         * @param  string $name
         * @param  mixed  $value
         *
         * @return void
         */
        public function changeValue($name, &$value) {
            if($field = \detemiro::fieldsControl()->getItem($name)) {
                if($common = \detemiro::actions()->getZone("usersfields.change.{$field['type']}")) {
                    foreach($common as $action) {
                        $value = $action->make($value, $this);
                    }
                }
                if($spec = \detemiro::actions()->getZone("usersfields.change.{$field['type']}.{$field['name']}")) {
                    foreach($spec as $action) {
                        $value = $action->make($value, $this);
                    }
                }
            }
        }

        /**
         * Проверка значения для поля
         *
         * @zones usersfields.check.{$field_type},usersfields.check.{$field_type}.{$name}
         *
         * @param  string $name
         * @param  mixed  $value
         *
         * @return bool|null
         */
        public function checkValue($name, $value) {
            $valide = null;

            if($field = \detemiro::fieldsControl()->getItem($name)) {
                if($field['require'] == true && is_bool($value) == false && $value !== 0 && $value == false) {
                    $valide = false;
                }
                else {
                    $handler = \detemiro::actions()->makeCheckZone(
                        "usersfields.change.{$field['type']}", $value, $field
                    );

                    if($handler === false) {
                        $valide = false;
                    }
                    else {
                        $sub = \detemiro::actions()->makeCheckZone(
                            "usersfields.change.{$field['type']}.{$field['name']}", $value, $field
                        );

                        if($sub === false) {
                            $valide = false;
                        }
                        elseif($sub || $handler) {
                            $valide = true;
                        }
                        else {
                            $valide = null;
                        }
                    }
                }
            }

            return $valide;
        }
    }
?>