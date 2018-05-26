<?php
    namespace detemiro;

    /**
     * Трейт-одиночка (Singleton)
     *
     * Данный метод позволяет создавать для класса только один объект.
     * Он запрещает использовать конструктор, блокирует клонирование и сериализацию.
     *
     */
    trait single {
        /**
         * Объект
         *
         * @var object|null $instance
         */
        private static $instance;

        /**
         * Возвращает поле $instance
         *
         * @return object
         */
        private static function getInstance() {
            if(self::$instance == null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct() {}
        public function __sleep() {}
        public function __wakeup() {}
        public function __clone() {}
    }
?>