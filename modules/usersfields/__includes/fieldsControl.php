<?php
    namespace detemiro\modules\usersfields;

    /**
     * Класс для работы с полями
     *
     * @services fieldsControler
     */
    class fieldsControl extends \detemiro\modules\database\data {
        protected $table = 'users_fields';

        protected $primary = array(
            'name'
        );

        protected $tmp;

        public function delete(array $cond) {
            $old = $this->get(array('cond' => $cond));
            if(parent::delete($cond)) {
                foreach($old as $item) {
                    \detemiro::usersFields()->deleteField($item['name']);
                }

                return true;
            }
            else {
                return false;
            }
        }

        public function __check_name($value) {
            return $this->__check_code($value);
        }
        public function __change_name(&$value) {
            $this->__change_code($value);
        }
        public function __check_type($value) {
            return $this->__check_code($value);
        }
        public function __change_type(&$value) {
            $this->__change_code($value);
        }
        public function __change_require(&$value) {
            $value = ($value) ? 1 : 0;
        }
        public function __get_require(&$value) {
            $value = ($value) ? true : false;
        }

        protected function __updateBeforeHandler(array &$par, array $cond = null) {
            if(parent::__updateBeforeHandler($par, $cond) === false) {
                return false;
            }
            elseif(array_key_exists('name', $par)) {
                $this->tmp = $this->get(array('cond' => $cond));
            }
            else {
                $this->tmp = null;
            }

            return true;
        }
        protected function __updateSuccessHandler(array $par, array $cond = null) {
            if($this->tmp) {
                foreach($this->tmp as $field) {
                    if($par['name'] != $field['name']) {
                        \detemiro::usersFields()->renameField($field['name'], $par['name']);
                    }
                }

                $this->tmp = null;
            }
        }
    }
?>