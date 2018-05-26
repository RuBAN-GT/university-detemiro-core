<?php
    namespace detemiro\modules\basic;

    trait basicControl {
        /**
         * Права объекта
         *
         * @var array $rules
         */
        protected $rules = array();
        protected function __set_rules($value) {
            if(is_string($value) || is_numeric($value) || is_array($value)) {
                $this->rules = \detemiro\take_good_array($value, true);
            }
        }
        /**
         * Проверка прав у текущего пользователя
         *
         * @return bool
         */
        public function checkRules() {
            return \detemiro::user()->checkRules($this->rules);
        }
        /**
         * Группы объекта
         *
         * @var array $groups
         */
        protected $groups = array();
        protected function __set_groups($value) {
            if(is_string($value) || is_numeric($value) || is_array($value)) {
                $this->groups = \detemiro\take_good_array($value, true);
            }
        }
        /**
         * Проверка групп у текущего пользователя
         *
         * @return bool
         */
        public function checkGroups() {
            return \detemiro::user()->checkGroups($this->groups);
        }
        /**
         * Общая проверка у текущего пользователя
         *
         * @return bool
         */
        public function isAllow() {
            return ($this->checkRules() && $this->checkGroups());
        }
    }
?>