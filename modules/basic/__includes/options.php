<?php
    namespace detemiro\modules\basic;

    /**
     * Опции
     *
     * @services options
     */
    class options extends \detemiro\modules\database\data {
        protected $primary = array(
            'family',
            'code'
        );

        public function __check_family($value) {
            return $this->__check_code($value);
        }
        public function __change_family(&$value) {
            $this->__change_code($value);
        }

        /**
         * Получение значения опции
         * 
         * @param  array $par Первичные ключи
         *
         * @return mixed
         */
        public function getValue($par) {
            if($res = $this->getItem($par)) {
                return $res['value'];
            }

            return $res;
        }

        /**
         * Получение семейства опций
         * Результатом является массив, ключами которого являются коды.
         *
         * @param  string $family
         *
         * @return array|null
         */
        public function getFamily($family) {
            if($par = $this->getCond($family, 'family')) {
                if($res = $this->db->select(array(
                    'table' => $this->table,
                    'cols'  => 'code,value',
                    'cond'  => $par
                ))) {
                    $this->__getHandler($res);

                    $tmp = array();
                    foreach($res as $item) {
                        $tmp[$item['code']] = $item['value'];
                    }

                    return $tmp;
                }
            }

            return null;
        }

        /**
         * Установка значения опции, если оно существует, или его добавление, в противном случае
         * 
         * @param  array $par   Первичные ключи
         * @param  mixed $value
         *
         * @return bool
         */
        public function set($par, $value = null) {
            if($this->exists($par)) {
                return $this->updateItem($par, array('value' => $value));
            }
            elseif($par = $this->getParam($par)) {
                return $this->add(array_merge($par, array('value' => $value)));
            }

            return false;
        }

        /**
         * Обновление значения
         *
         * @param  array $par Первичные ключи
         * @param  mixed $value
         *
         * @return bool
         */
        public function updateValue($par, $value) {
            return $this->updateItem($par, array('value' => $value));
        }

        /**
         * Удаления семейства опций
         * 
         * @param  string $family
         *
         * @return bool
         */
        public function deleteFamily($family) {
            if($cond = $this->getCond($family, 'family')) {
                return $this->delete($cond);
            }

            return false;
        }
    }
?>