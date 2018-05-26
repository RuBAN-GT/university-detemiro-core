<?php
    namespace detemiro\modules;

    /**
     * Класс зависимости
     */
    class relation extends \detemiro\magicControl {
        /**
         * Статус зависимости
         *
         * @var bool|null
         */
        protected $status = null;
        protected function __set_status($value) {
            $this->status = (bool) $value;
        }

        /**
         * Имя зависимого модуля
         *
         * @var string $name
         */
        protected $name = '';
        protected function __set_name($value) {
            if($value && is_string($value)) {
                $this->name = $value;
            }
        }

        /**
         * Метод зависимости
         *
         * @var string $method
         */
        protected $method = '';
        protected function __set_method($value) {
            if(in_array($value, relationsCollector::allows())) {
                $this->method = $value;
            }
        }

        /**
         * Конструктор
         *
         * @param array $data
         *
         * @throws \Exception Если отношение задано не полностью.
         */
        public function __construct(array $data) {
            $this->__propUpdate($data);

            if($this->method == '' || $this->name == '') {
                throw new \Exception('В отношении необходимо указать модуль и метод работы с ним.');
            }
            else {
                $this->__ignoreSET[] = 'name';
                $this->__ignoreSET[] = 'method';
            }
        }
    }
?>