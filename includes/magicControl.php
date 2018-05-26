<?php
    namespace detemiro;

    /**
     * Класс, позволяющий обрабатывать поля класса через "магию" PHP
     */
    class magicControl {
        /**
         * Игнорируемые поля для выдачи и установки
         */
        protected $__ignoreSET = array();
        protected $__ignoreGET = array();

        /**
         * Добавленные поля при вставке
         *
         * @var array $__allowSET
         */
        protected $__allowSET = array();

        /**
         * Заполнение полей
         *
         * Данный метод позволяет заполнять поля объекта по ключам ассоциативного массива, с использованием метода __set.
         *
         * @param  array $par Ассоциативный массив
         *
         * @return void
         */
        protected function __propUpdate(array $par) {
            foreach($par as $key=>$item) {
                $this->__set($key, $item);
            }
        }

        /**
         * Получение поля
         *
         * @param string $key Ключ поля
         *
         * @return mixed
         */
        public function __get($key) {
            if(in_array($key, $this->__ignoreGET) == false) {
                $try = "__get_$key";

                if(method_exists($this, $try)) {
                    return $this->$try();
                }
                elseif(property_exists($this, $key)) {
                    return $this->$key;
                }
            }

            return null;
        }

        /**
         * Проверка существования поля
         *
         * @param  string $key Ключ поля
         *
         * @return bool
         */
        public function __isset($key) {
            return (property_exists($this, $key) && in_array($key, $this->__ignoreGET) == false);
        }

        /**
         * Заполнение-обработка
         *
         * Данный метод изменяет значения поля $key на значение $value, вызывая метод set_{$key},
         * если он существует, или осуществляет присваивание в противной случае.
         *
         * @param  string $key   Имя поля
         * @param  mixed  $value Значение поля
         *
         * @return void
         */
        public function __set($key, $value) {
            if(in_array($key, $this->__ignoreSET) == false) {
                $try = "__set_$key";

                if(method_exists($this, $try)) {
                    $this->$try($value);
                }
                elseif(in_array($key, $this->__allowSET)) {
                    $this->$key = $value;
                }
                elseif(property_exists($this, $key) == false) {
                    $this->$key = $value;

                    $this->__allowSET[] = $key;
                }
            }
        }
    }
?>