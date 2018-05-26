<?php
    namespace detemiro\modules\loginAndPass;

    class users extends \detemiro\modules\basic\users {
        /**
         * Получение ID пользователя по его логину
         *
         * @param  string $login
         *
         * @return int|null
         */
        public function getIDbyLogin($login) {
            if(is_string($login) && $login) {
                return $this->db->select(array(
                    'table'  => $this->table,
                    'cols'   => 'id',
                    'oneRow' => 0,
                    'oneCol' => 0,
                    'cond'   => array(
                        'param' => 'login',
                        'value' => $login
                    )
                ));
            }
            else {
                return null;
            }
        }

        /**
         * Генерация пароля
         *
         * Даный метод односторонне генерирует пароль по строке и соли (если они не заданы, то создаёт их) и возвращает результат в виде массиве с ключами 'password', 'hash', 'salt'.
         *
         * @see     \detemiro\random_hash()
         *
         * @param  string $password Пароль-строка
         * @param  string $salt     Соль
         * @param  bool   $md5      Хеширование пароля в мд5-хеш
         *
         * @return array
         */
        public static function cryptPass($password = '', $salt = '', $md5 = true) {
            $res = array(
                'password' => $password,
                'hash'     => '',
                'salt'     => $salt
            );

            if($res['salt'] == '') {
                $res['salt'] = '$2a$10$' . \detemiro\random_hash(22, false) . '$';
            }
            if($res['password'] == '') {
                $res['password'] = \detemiro\random_hash(16);
            }

            if($md5) {
                $res['password'] = md5($res['password']);
            }

            $res['hash'] = crypt($res['password'], $res['salt']);

            return $res;
        }

        /**
         * Проверка пароля пользователя
         *
         * @param  int    $id
         * @param  string $password
         * @param  bool   $md5
         *
         * @return bool
         */
        public function checkPassword($id, $password, $md5 = true) {
            if(is_string($password) && $password) {
                if($user = $this->getItem($id)) {
                    $password = self::cryptPass($password, $user['salt'], $md5)['hash'];

                    return ($password == $user['password']);
                }
            }

            return false;
        }

        protected function __addBeforeHandler(array &$par) {
            if(parent::__addBeforeHandler($par) === false) {
                return false;
            }
            elseif(isset($par['password'])) {
                if($par['password'] && is_string($par['password'])) {
                    $par['password'] = self::cryptPass($par['password']);

                    $par['salt']     = $par['password']['salt'];
                    $par['password'] = $par['password']['hash'];
                }
                else {
                    return false;
                }
            }

            return true;
        }
        protected function __updateBeforeHandler(array &$par, array $cond = null) {
            if(parent::__updateBeforeHandler($par, $cond) === false) {
                return false;
            }
            elseif(isset($par['password'])) {
                if($par['password'] && is_string($par['password'])) {
                    $par['password'] = self::cryptPass($par['password']);

                    $par['salt']     = $par['password']['salt'];
                    $par['password'] = $par['password']['hash'];
                }
                else {
                    return false;
                }
            }

            return true;
        }

        public function __change_login(&$value) {
            $value = htmlspecialchars($value, \ENT_QUOTES);
        }
    }
?>