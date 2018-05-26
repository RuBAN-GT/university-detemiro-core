<?php
    namespace detemiro\events;

    /**
     * Объект коллектора событий
     *
     * @throws \Exception Если код объекта указан неверно.
     */
    class object extends \detemiro\magicControl {
        /**
         * Код объекта
         * 
         * @var string $code
         */
        protected $code;
        protected function __set_code($value) {
            if(is_numeric($value)) {
                $value = (string) $value;
            }

            if(is_string($value)) {
                $value = \detemiro\canone_code($value);

                if(\detemiro\validate_code($value)) {
                    $this->code = $value;
                }
            }
        }

        /**
         * Код пространства
         * 
         * @var string $space
         */
        protected $space;
        protected function __set_space($value) {
            if($value == null || is_string($value) || is_array($value)) {
                $this->space = \detemiro::space()->getCode($value);
            }
        }

        /**
         * Функция объекта
         * 
         * @var resource|null $function
         */
        protected $function;
        protected function __set_function($value) {
            if($value == null) {
                $this->function = null;
            }
            elseif(is_callable($value)) {
                $this->function = $value->bindTo($this, $this);
            }
        }

        /**
         * Источник объекта
         *
         * @var string|null $source
         */
        protected $source;
        protected function __set_source($value) {
            if($value == null || is_string($value)) {
                $this->source = $value;
            }
        }

        /**
         * Исполнение функции объекта, если она задана
         *
         * Данный метод выполняет функцию объекта, помещая в неё аргумента, отправленные в него.
         * 
         * @return mixed
         */
        protected function doit() {
            return ($this->function) ? call_user_func_array($this->function, func_get_args()) : null;
        }

        /**
         * Создание объекта по ассоц. массиву
         *
         * @param array $obj
         *
         * @throws \Exception Если код объекта указан неверно.
         */
        public function __construct(array $obj) {
            if(array_key_exists('code', $obj) == false) {
                $this->code = 'unnamed-' . \detemiro\random_hash(15, false);
            }

            $this->__propUpdate($obj);

            if($this->code == null) {
                throw new \Exception('Объект должен иметь верный код.');
            }

            $this->space = \detemiro::space()->getCode($this->space);
        }
    }
?>