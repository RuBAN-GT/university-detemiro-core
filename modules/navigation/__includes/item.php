<?php
    namespace detemiro\modules\navigation;

    class item extends \detemiro\magicControl {
        /**
         * Код элемента
         *
         * @var string $code
         */
        protected $code;
        protected function __set_code($value) {
            if($value && (is_string($value) || is_numeric($value))) {
                $this->code = $value;
            }
        }

        /**
         * Заголовок элемента
         *
         * @var bool $title
         */
        protected $title;
        protected function __set_title($value) {
            if(is_string($value) || is_numeric($value)) {
                $this->title = $value;
            }

            if($this->title == null) {
                $this->updateAuto('title');
            }
        }

        /**
         * Это страница
         *
         * @var bool $page
         */
        protected $page = true;
        protected function __set_page($value) {
            $this->page = (bool) $value;
        }

        /**
         * Значение элемента (ссылка или код страницы)
         *
         * @var string $value
         */
        protected $value = '';
        protected function __set_value($value) {
            if($value == null) {
                $this->value = '';
            }
            elseif(is_string($value) || is_numeric($value)) {
                if($this->page) {
                    $value = \detemiro::pages()->makeFullKey($value);

                    if($this->value != $value) {
                        $this->value = $value;

                        $this->updateAuto();
                    }
                }
                else {
                    $this->value = $value;
                }
            }
        }

        /**
         * Код родительского элемента
         *
         * @var string $parent
         */
        protected $parent = '';
        protected function __set_parent($value) {
            if($value == null || is_string($value) || is_numeric($value)) {
                $this->parent = $value;
            }
        }

        /**
         * Порядок элемента
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
         * Конструктор
         *
         * @param array $body Поля элемента
         */
        public function __construct(array $body = array()) {
            if($body) {
                $this->__propUpdate($body);

                if($this->page) {
                    $this->updateAuto();
                }
            }
        }

        /**
         * Автоматическое обновление полей ($key) по странице
         *
         * @param string $key Поле
         *
         * @return void
         */
        protected function updateAuto($key = '') {
            if($key == '' || $key == 'title') {
                if($this->title == null) {
                    if($this->page && $this->value) {
                        if($page = \detemiro::pages()->get($this->value)) {
                            $this->title = $page->title;
                        }
                        else {
                            $this->title = 'Undefined';
                        }
                    }
                    elseif($this->title == '') {
                        $this->title = 'Unknown';
                    }
                }
            }
        }

        /**
         * Обновление элемента
         *
         * @param array $item
         */
        public function update(array $item) {
            $this->__propUpdate($item);
        }

        /**
         * Получение ссылки на элемент
         *
         * @return string
         */
        public function getLink() {
            if($this->page) {
                return \detemiro::router()->getLink($this->value);
            }
            else {
                return $this->value;
            }
        }
    }
?>