<?php
    namespace detemiro\modules\pages;

    /**
     * Объект страницы
     */
    class page extends \detemiro\events\object {
        /**
         * Ключ страницы
         *
         * Код, формируемый на базе родительского и текущего.
         * 
         * @var string $key
         */
        protected $key;

        protected function __set_code($value) {
            if(is_numeric($value)) {
                $value = (string) $value;
            }

            if($value && is_string($value)) {
                $try = explode('/', $value);

                $last = \detemiro\canone_code(array_pop($try));

                if(\detemiro\validate_code($last)) {
                    $this->code = $last;

                    if($try) {
                        $this->parent = implode('-', $try);
                    }

                    $this->key = $this->parent . ($this->parent ? '-' : '') . $this->code;
                }
            }
        }

        /**
         * Заголовок страницы
         * 
         * @var string $title
         */
        protected $title;
        protected function __set_title($value) {
            if($value == null || is_string($value)) {
                $this->title = $value;
            }
        }

        /**
         * Код родительской страницы
         * 
         * @var string $parent
         */
        protected $parent;
        protected function __set_parent($value) {
            if($value == null || is_string($value)) {
                $this->parent = $value;

                $this->key = $this->parent . ($this->parent ? '-' : '') . $this->code;
            }
        }

        public function __construct(array $obj) {
            parent::__construct($obj);

            $this->key = $this->parent . ($this->parent ? '-' : '') . $this->code;
        }

        public function __set($key, $value) {
            if($key != 'key') {
                parent::__set($key, $value);
            }
        }

        /**
         * Обработчик полей
         *
         * @zones page.check.{$поле}
         *
         * @return bool
         */
        public function isAllow() {
            foreach($this as $key=>$value) {
                if(
                    in_array($key, array('function', 'code', 'key', 'parent')) == false &&
                    \detemiro::actions()->makeCheckZone("page.check.$key", $value) === false
                )
                {
                    return false;
                }
            }

            return true;
        }

        /**
         * Выполнение функции страницы с обработчиками
         *
         * @zones page.before.show, page.before.{$key}, page.after.show, page.after.{$key}
         * 
         * @return void
         */
        public function show() {
            if($this->isAllow()) {
                \detemiro::actions()->makeZone("page.before.{$this->key}", $this);
                \detemiro::actions()->makeZone("page.before.show", $this);

                $args = func_get_args();
                if(count($args) == 0) {
                    if($try = \detemiro::registry()->get("page.{$this->key}")) {
                        $args = array_merge(array($try), $args);
                    }
                }

                call_user_func_array(array($this,'doit'), $args);

                \detemiro::actions()->makeZone("page.after.{$this->key}", $this);
                \detemiro::actions()->makeZone("page.after.show", $this);
            }
        }
    }
?>