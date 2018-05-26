<?php
    namespace detemiro\events;

    /**
     * Экшен
     */
    class action extends object {
        /**
         * Приоритет выполнения экшена в зоне
         * 
         * @var int $priority
         */
        protected $priority = 0;
        protected function __set_priority($value) {
            if(is_numeric($value) || is_bool($value)) {
                $this->priority = $value;
            }
        }

        /**
         * Зона экшена
         * 
         * @var string $zone
         */
        protected $zone;
        protected function __set_zone($value) {
            if($value == null || is_string($value)) {
                $this->zone = $value;
            }
        }

        /**
         * Выполнение функции экшена
         * 
         * @return mixed
         */
        public function make() {
            return call_user_func_array(array($this, 'doit'), func_get_args());
        }
    }
?>