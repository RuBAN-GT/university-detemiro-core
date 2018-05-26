<?php
    namespace detemiro;

    /**
     * Регистр
     *
     * Данный класс создаёт регистр (ключ - значение), с возможностью блокировки ключей от изменения.
     */
    class registry {
        /**
         * Регистр
         * 
         * @var array $list
         */
        protected $list  = array();

        /**
         * Возвращает поле $list
         * 
         * @return array
         */
        public function all() {
            $res = array();

            foreach($this->list as $key=>$value) {
                $res[$key] = $this->get($key);
            }

            return $res;
        }

        /**
         * Поиск значения по префиксу в ключе
         *
         * @param  string $prefix
         * @param  bool   $clear  Очиска префикса из найденных ключей
         *
         * @return array
         */
        public function getByPrefix($prefix, $clear = false) {
            $res = array();

            foreach($this->list as $key=>$value) {
                if(strpos($key, $prefix) === 0) {
                    $new = ($clear) ? substr($key, strlen($prefix)) : $key;

                    $res[$new] = $this->get($key, array_slice(func_get_args(), 2));
                }
            }

            return $res;
        }

        /**
         * Установка ключа $key
         *
         * Данный метод задаёт значение $value ключа $key и может заблокировать ключ от изменение с $lock = true.
         *
         * @param  string $key
         * @param  mixed  $value
         * @param  bool   $lock
         *
         * @return bool
         */
        public function set($key, $value, $lock = false) {
            if($this->exists($key) == false || $this->list[$key]['lock'] == false) {
                $this->list[$key] = array(
                    'value' => $value,
                    'lock'  => (bool) $lock
                );

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Получение значения ключа
         * 
         * @param  string $key
         *
         * @return mixed
         */
        public function get($key) {
            if($this->exists($key)) {
                if(is_object($this->list[$key]['value']) && $this->list[$key]['lock'] == true) {
                    return clone $this->list[$key]['value'];
                }
                else {
                    return $this->list[$key]['value'];
                }
            }
            else {
                return null;
            }
        }

        /**
         * Проверка существования ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function exists($key) {
            return (array_key_exists($key, $this->list));
        }

        /**
         * Удаление ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function remove($key) {
            if($this->exists($key) && $this->list[$key]['lock'] == false) {
                unset($this->list[$key]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Проверика статуса закрытости ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function checkLock($key) {
            return ($this->exists($key) && $this->list[$key]['lock']);
        }

        /**
         * Закрытие ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function lock($key) {
            if($this->exists($key)) {
                $this->list[$key]['lock'] = true;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Открытие ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function unLock($key) {
            if($this->exists($key)) {
                $this->list[$key]['lock'] = false;

                return true;
            }
            else {
                return false;
            }
        }

        public function __get($key) {
            return $this->get($key);
        }
        public function __isset($key) {
            return $this->exists($key);
        }
        public function __unset($key) {
            $this->remove($key);
        }
        public function __set($key, $value) {
            return $this->set($key, $value);
        }
    }
?>