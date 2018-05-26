<?php
    namespace detemiro;

    /**
     * Класс пространства
     *
     * Данный класс служит для определения и считывания информации о пространстве.
     */
    class space extends \detemiro\magicControl {
        /**
         * Код пространства
         * 
         * @var string $code
         */
        protected $code;
        protected function __set_code($value) {
            if(is_string($value)) {
                $value = \detemiro\canone_code($value);

                if(\detemiro\validate_code($value)) {
                    $this->code = $value;
                }
            }
        }

        /**
         * Имя пространства
         * 
         * @var string $name
         */
        protected $name;
        protected function __set_name($value) {
            if(is_string($value)) {
                $this->name = $value;
            }
        }

        /**
         * Путь до корня пространства
         * 
         * @var string $path
         */
        protected $path;

        /**
         * Зависимости проекта
         *
         * @var array $rels
         */
        protected $rels;
        protected function __set_rels($value) {
            if(is_array($value)) {
                $this->rels = $value;
            }
        }

        /**
         * Статус определения пространства среди других
         * 
         * @var bool $status
         */
        protected $status = false;

        /**
         * Индивидуальная конфигурация пространства
         *
         * @var array $config
         */
        protected $config = array();
        protected function __set_config($value) {
            if(is_array($value)) {
                $this->config = $value;
            }
        }

        /**
         * Получение кода пространства
         *
         * Данный метод выдаёт текущий код пространства, если $code равняется local или не задано, 
         * в противном случае возвращается сам $code, если он строка, или первое значение, если массив.
         * 
         * @param  array|string $code
         *
         * @return string|null
         */
        public function getCode($code = 'local') {
            if($code == '') {
                return $this->code;
            }
            elseif(is_string($code)) {
                if($code == 'local') {
                    return $this->code;
                }
                else {
                    $code = explode(',', $code);
                }
            }
            
            if(is_array($code)) {
                if(in_array($this->code, $code) || in_array('local', $code)) {
                    return $this->code;
                }
                else {
                    return array_shift($code);
                }
            }
            else {
                return null;
            }
        }

        /**
         * Инициализация пространства
         *
         * Конструктор определяет текущее пространства. Можно задать $pre_space, если это массив - то следует в нём указать поля пространства, если строка - то код нужного пространства.
         * 
         * @param array|string|null $need
         */
        public function __construct($need = null) {
            /**
             * Определение пути до корня пространства (местонахождения исполняющего файла)
             */
            $breads = array_reverse(debug_backtrace(false));
            $path   = '';

            foreach($breads as $piece) {
                if(is_assoc_array($piece) && isset($piece['class'], $piece['function']) && $piece['class'] == 'detemiro' && $piece['function'] == 'main') {
                    $path = $piece['file'];
                    break;
                }
            }
            $this->path = \detemiro\norm_path(realpath(dirname($path)));

            $try = null;

            /**
             * Поиск пространства
             */
            if(is_array($need)) {
                $try = $need;
            }
            elseif($need && is_string($need)) {
                $try = read_json("{$this->path}/det-space/space.{$need}.json");

                $this->code = $need;
            }
            else {
                $try = read_json("{$this->path}/det-space/space.json");
            }

            if($try) {
                $this->__propUpdate($try);

                $this->status = true;
            }

            /**
             * Код
             */
            if($this->code == '') {
                $this->code = 'default';
            }

            /**
             * Имя
             */
            if($this->name == null || is_string($this->name) == false) {
                $this->name = 'unnamed';
            }

            /**
             * Закрываю поля
             */
            $this->__ignoreSET[] = 'rels';
            $this->__ignoreSET[] = 'config';
            $this->__ignoreSET[] = 'code';
        }

        /**
         * Загрузчик класса из директории includes проекта
         * 
         * @param  string $name
         *
         * @return bool
         */
        public function classLoader($name) {
            if(strpos($name, 'detemiro\\space\\') === 0 && ($name = substr($name, 15))) {
                $try = "{$this->path}/det-includes/__includes/$name.php";

                if(file_exists($try)) {
                    include($try);

                    return true;
                }
            }

            return false;
        }
    }
?>