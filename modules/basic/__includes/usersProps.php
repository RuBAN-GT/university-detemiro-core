<?php
    namespace detemiro\modules\basic;

    /**
     * Класс управления полями (properties) пользователя
     *
     * @services usersProps
     */
    class usersProps extends options {
        protected $table = 'users_properties';

        protected $primary = array(
            'user_id',
            'family',
            'code'
        );

        public function __check_user_id($value) {
            return (is_numeric($value));
        }

        /**
         * Получение семейство свойств
         * Результатом является массив, ключём которого является id пользователя,
         * а значением - массив код->значение
         *
         * @param string $family
         *
         * @return array|null
         */
        public function getFamily($family) {
            if($cond = $this->getCond($family, 'family')) {
                if($res = $this->db->select(array(
                    'table' => $this->table,
                    'cols'  => 'user_id,code,value',
                    'cond'  => $cond
                ))) {
                    $this->__getHandler($res);

                    $tmp = array();
                    foreach($res as $item) {
                        $tmp[$item['user_id']][$item['code']] = $item['value'];
                    }

                    return $tmp;
                }
            }

            return null;
        }

        /**
         * Получение семейства полей для пользователя
         *
         * @param  int    $ID
         * @param  string $family
         *
         * @return array|null
         */
        public function getUserFamily($ID, $family) {
            if($cond = $this->getCond(array($ID, $family), 'user_id,family')) {
                if($res = $this->db->select(array(
                    'table' => $this->table,
                    'cols'  => 'code,value',
                    'cond'  => $cond
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
         * Получение всех полей для пользователя
         *
         * @param  int $ID
         *
         * @return array|null
         */
        public function getAll($ID) {
            if($par = $this->getCond($ID, 'user_id')) {
                if($this->db->select(array(
                    'table' => $this->table,
                    'cols'  => 'family,code,value',
                    'cond'  => $par
                ))) {
                    $this->__getHandler($res);

                    return $res;
                }
            }

            return null;
        }

        /**
         * Удаления семейства полей для пользователя
         *
         * @param  int    $ID
         * @param  string $family
         *
         * @return bool
         */
        public function deleteUserFamily($ID, $family) {
            if($cond = $this->getCond(array($ID, $family), 'user_id,family')) {
                return $this->delete($cond);
            }

            return false;
        }
    }
?>
