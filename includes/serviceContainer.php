<?php
    namespace detemiro;

    /**
     * Локатор сервисов
     *
     * Регистр с инициализацией значений.
     */
    class serviceContainer extends registry {
        /**
         * Установка значения
         *
         * @param string $key
         * @param mixed  $value
         * @param bool   $lock
         * @param bool   $init  $value является инициализирующим ресурсом
         *
         * @return bool
         * @throws \Exception
         */
        public function set($key, $value, $lock = false, $init = false) {
            if(array_key_exists($key, $this->list) == false || $this->list[$key]['lock'] == false) {
                $init = (bool) $init;

                if($init) {
                    if(is_callable($value)) {
                        $init  = $value;
                        $value = null;
                    }
                    else {
                        throw new \Exception('Инициализирующее значение должно быть вызываемо (ресурс).');
                    }
                }

                $this->list[$key] = array(
                    'value'  => $value,
                    'lock'   => (bool) $lock,
                    'init'   => $init,
                    'inited' => !$init
                );

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Выполнение инициализирующего ресурса
         *
         * Метод возвращает результат ресурса, если он существует, в противном случае false.
         *
         * @param $key
         *
         * @return bool|mixed
         */
        public function init($key) {
            if($this->exists($key)) {
                $args = array_slice(func_get_args(), 1);

                return call_user_func_array($this->list[$key]['init'], $args);
            }
            else {
                return false;
            }
        }

        /**
         * Переинициализация значения
         *
         * @param $key
         *
         * @return bool
         */
        public function initValue($key) {
            if($this->exists($key) && $this->list[$key]['init']) {
                $args = array_slice(func_get_args(), 1);

                try {
                    $this->list[$key]['value'] = call_user_func_array($this->list[$key]['init'], $args);

                    $this->list[$key]['inited'] = true;

                    return true;
                }
                catch(\Exception $error) {
                    return false;
                }
            }
            else {
                return false;
            }
        }

        /**
         * Сброс инициализации
         *
         * @param string $key
         *
         * @return bool
         */
        public function reset($key) {
            if($this->exists($key) && $this->list[$key]['inited']) {
                $this->list[$key]['value']  = null;
                $this->list[$key]['inited'] = false;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Получение значения
         *
         * При получения значения может произойти инициализация, в случае её провала результатом будет являться false.
         *
         * @param string $key
         *
         * @return mixed
         */
        public function get($key) {
            if($this->exists($key)) {
                if(is_object($this->list[$key]['value']) && $this->list[$key]['lock'] == true) {
                    return clone $this->list[$key]['value'];
                }
                elseif($this->list[$key]['init'] && $this->list[$key]['inited'] == false) {
                    $args = array_slice(func_get_args(), 1);

                    try {
                        $this->list[$key]['value'] = call_user_func_array($this->list[$key]['init'], $args);

                        $this->list[$key]['inited'] = true;
                    }
                    catch(\Exception $error) {
                        return false;
                    }
                }

                return $this->list[$key]['value'];
            }
            else {
                return null;
            }
        }
    }
?>