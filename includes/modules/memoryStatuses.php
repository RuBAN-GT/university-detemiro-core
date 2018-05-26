<?php
    namespace detemiro\modules;

    /**
     * Контроль статусов модулей через регистр
     */
    class memoryStatuses implements iStatuses {
        /**
         * Статусы модулей
         *
         * @var array $content
         */
        protected $content = array();

        public function get($code) {
            if(array_key_exists($code, $this->content)) {
                return (int) $this->content[$code];
            }
            else {
                return null;
            }
        }

        public function set($code, $status) {
            $this->content[$code] = (int) $status;

            return true;
        }

        public function remove($code) {
            if(array_key_exists($code, $this->content)) {
                unset($this->content[$code]);

                return true;
            }
            else {
                return false;
            }
        }
    }
?>