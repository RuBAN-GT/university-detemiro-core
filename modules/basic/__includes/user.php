<?php
    namespace detemiro\modules\basic;

    /**
     * Управление пользователем
     *
     * @services user
     */
    class user {
        /**
         * Объект для работы с БД
         * 
         * @var \detemiro\modules\database\dbActions $db
         */
        protected $db;

        /**
         * Объект для работы с пользователями
         * 
         * @var \detemiro\modules\basic\users $usersReg
         */
        protected $usersReg;

        /**
         * Объект для работы с группами
         * 
         * @var \detemiro\modules\basic\groups $groupsReg
         */
        protected $groupsReg;

        /**
         * Массив прав гостей
         * 
         * @var array $guest
         */
        protected $guest = array();

        /**
         * Параметры выбранного пользователя (не поля!)
         * 
         * @var array $cols
         */
        protected $cols = array();

        /**
         * Получение массива параметров пользователя
         * 
         * @return array
         */
        public function cols() {
            return $this->cols;
        }

        /**
         * Получение параметров пользователя через магию
         * 
         * @param  string $key
         *
         * @return mixed
         */
        public function __get($key) {
            if(array_key_exists($key, $this->cols)) {
                return $this->cols[$key];
            }
            else {
                return null;
            }
        }

        /**
         * Магическая проверка параметра пользователя
         *
         * @param  string $key
         *
         * @return bool
         */
        public function __isset($key) {
            return (array_key_exists($key, $this->cols));
        }

        /**
         * Магическая установка параметра
         *
         * @param $key
         * @param $value
         *
         * @return void
         */
        public function __set($key, $value) {
            if(array_key_exists($key, $this->cols)) {
                $this->update(array(
                    $key => $value
                ));
            }
        }

        public function __call($name, $args) {
            return \detemiro::services()->get("user.$name", $args);
        }

        public function __construct(\detemiro\modules\database\dbActions $db, \detemiro\modules\basic\users $users, \detemiro\modules\basic\groups $groups) {
            $this->db        = $db;
            $this->usersReg  = $users;
            $this->groupsReg = $groups;

            if($current = $this->usersReg->struct()) {
                $this->guest = array_fill_keys($current, null);
            }

            $this->guest['id']     = 0;
            $this->guest['groups'] = array('guests');
            $this->guest['rules']  = array('guest');
            $this->guest['ip']     = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null;
            $this->guest['agent']  = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null;
            $this->guest['check']  = false;

            if($current = $this->groupsReg->getRules('guests')) {
                if(in_array('guest', $current) == false) {
                    $current[] = 'guest';
                }
                $this->guest['rules'] = $current;
            }

            $this->init(0);
        }

        /**
         * Инициализировать пользователя в системе
         *
         * Данный метод инициализирует пользователя по его ID, если $ID = 0, то пользователь станет гостем.
         * 
         * @param  int  $ID
         *
         * @return bool
         */
        public function init($ID = 0) {
            if($ID == 0) {
                $this->cols = $this->guest;

                \detemiro::actions()->makeZone('basic.user.guest-init');

                return true;
            }

            if(is_numeric($ID) && $ID > 0 && ($current = $this->usersReg->getItem($ID, true))) {
                $this->cols = array_replace($this->cols, $current);

                $this->cols['check'] = true;

                $this->usersReg->updateItem($ID, array(
                    'last_login' => date('c')
                ));

                \detemiro::actions()->makeZone('basic.user.real-init');

                return true;
            }
            else {
                return false;
            }
        }
        
        /**
         * Авторизация пользователя по хешу
         * 
         * @param  int    $ID
         * @param  string $hash
         *
         * @return bool
         */
        public function signInByHash($ID, $hash) {
            if($this->usersReg->checkHash($ID, $hash)) {
                if($this->init($ID)) {
                    \detemiro::actions()->makeZone('basic.user.signInByHash.success');

                    return true;
                }
            }

            return false;
        }
        
        /**
         * Полный выход
         *
         * Данный метод осуществляет обычный выход и в случае успеха удаляет хеш-авторизации пользователя.
         * 
         * @return bool
         */
        public function signOut() {
            $ID = $this->cols['id'];

            if($this->abort()) {
                $this->usersReg->updateItem($ID, array(
                    'hash' => null
                ));

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Выход
         *
         * Данный метод выкидывает текущего пользователя, делая его гостем.
         * 
         * @return bool
         */
        public function abort() {
            if($this->cols['check']) {
                \detemiro::actions()->makeZone('basic.user.abort.before');

                $this->init(0);

                \detemiro::actions()->makeZone('basic.user.abort.after');

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Проверка прав у текущего пользователя
         * 
         * @param array|string $codes
         *
         * @return bool
         */
        public function checkRules($codes) {
            if($codes == null) {
                return true;
            }
            else {
                return \detemiro\find_struct(\detemiro\take_good_array($codes, true), $this->cols['rules']);
            }
        }

        /**
         * Проверка группы
         * 
         * @param array|string $codes
         *
         * @return bool
         */
        public function checkGroups($codes) {
            if($codes == null) {
                return true;
            }
            else {
                return \detemiro\find_struct(\detemiro\take_good_array($codes, true), $this->cols['groups']);
            }
        }

        /**
         * Обновление параметров у пользователя
         * 
         * @param  array $par
         *
         * @return bool
         */
        public function update(array $par) {
            if($this->cols['check']) {
                if($this->usersReg->updateItem($this->cols['id'], $par)) {
                    $this->refresh();

                    return true;
                }
            }
            else {
                return false;
            }
        }

        /**
         * Обновление параметра
         *
         * @param string $key
         * @param mixed  $value
         *
         * @return bool
         */
        public function updateItem($key, $value) {
            return $this->update(array(
                $key => $value
            ));
        }

        /**
         * Обновление всех параметров пользователя
         *
         * @return void
         */
        public function refresh() {
            if($this->cols['check']) {
                if($current = $this->usersReg->getItem($this->cols['id'])) {
                    $this->cols = array_replace($this->cols, $current);
                }
            }
        }
    }
?>