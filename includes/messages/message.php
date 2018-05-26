<?php
    namespace detemiro\messages;

    /**
     * Сообщение
     *
     * Объект сообщения
     */
    class message extends \detemiro\magicControl {
        /**
         * Категория сообщения
         * 
         * @var string $type
         */
        protected $type = 'system';
        public function __set_type($value) {
            if(is_string($value)) {
                $this->type = $value;
            }
        }

        /**
         * Статус (подтип) сообщения
         * 
         * @var int $status
         */
        protected $status = 4;
        public function __set_status($value) {
            $tmp = collector::getStatusCode($value);

            if($tmp !== null) {
                $this->status = $tmp;
            }
        }

        /**
         * Текст сообщения
         * 
         * @var string $text
         */
        protected $text;
        public function __set_text($value) {
            if(is_string($value)) {
                $this->text = $value;
            }
        }

        /**
         * Заголовок сообщения
         * 
         * @var string $title
         */
        protected $title;
        public function __set_title($value) {
            if(is_string($value)) {
                $this->title = $value;
            }
        }

        /**
         * Количество отображений
         * 
         * @var int $save
         */
        protected $save = 1;
        public function __set_save($value) {
            if(is_numeric($value)) {
                $this->save = $value;
            }
        }

        /**
         * Дата создания сообщения
         * 
         * @var string $date
         */
        protected $date;

        /**
         * Создание объекта сообщения по ассоц. массиву
         * 
         * @param array $obj
         */
        public function __construct(array $obj) {
            $this->__propUpdate($obj);

            $this->date = date('c');
        }
    }
?>