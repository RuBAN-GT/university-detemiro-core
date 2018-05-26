<?php
    namespace detemiro\modules\router;

    /**
     * Объект схемы
     */
    class scheme extends \detemiro\magicControl {
        /**
         * Сама схема
         *
         * @var string $scheme
         */
        protected $scheme = '';
        protected function __set_scheme($value) {
            if($value && is_string($value)) {
                $this->scheme = trim($value, '/');
            }
            elseif($value == '') {
                $this->scheme = '';
            }
        }

        /**
         * Тип схемы (её код)
         *
         * @var string $type
        */
        protected $type = '';
        protected function __set_type($value) {
            if($value && is_string($value)) {
                $this->type = $value;
            }
            elseif($value == '') {
                $this->type = '';
            }
        }

        /**
         * Путь в схеме
         *
         * @var array $path
         */
        protected $path = array();
        protected function __set_path($value) {
            if($value && is_string($value)) {
                if($try = parse_url($value, PHP_URL_PATH)) {
                    $this->path = explode('/', $try);
                }
            }
            elseif($value == '') {
                $this->path = array();
            }
        }

        /**
         * Запрос в схеме
         *
         * @var array $query
         */
        protected $query = array();
        protected function __set_query($value) {
            if($value && is_string($value)) {
                $value = ($try = parse_url($value, PHP_URL_QUERY)) ? $try : '';
                parse_str($value, $this->query);
            }
            elseif($value == '') {
                $this->query = array();
            }
        }

        /**
         * Страница схемы
         *
         * @var string $page
         */
        protected $page = '';
        protected function __set_page($value) {
            if($value && is_string($value)) {
                $this->page = $value;
            }
            elseif($value == '') {
                $this->page = '';
            }
        }

        /**
         * Данные схемы
         *
         * @var array $data
         */
        protected $data = array();
        protected function __set_data($value) {
            if($value && is_array($value)) {
                $this->data = $value;
            }
            elseif($value == '') {
                $this->data = array();
            }
        }

        public function __construct(array $par) {
            $this->__propUpdate($par);

            if($this->scheme) {
                if($this->query == null) {
                    $this->__set_query($this->scheme);
                }
                if($this->path == null) {
                    $this->__set_path($this->scheme);
                }
            }
        }

        /**
         * Формирование строки с именем страницы и типа
         *
         * @return string
         */
        public function zoneName() {
            $zone  = 'router.scheme';
            if($this->page) {
                $zone .= '.' . $this->page;
            }
            if($this->type && is_string($this->type)) {
                $zone .= '.' . $this->type;
            }

            return $zone;
        }

        /**
         * Подстановка аргументов в переменные схемы
         *
         * @param  array $args
         *
         * @return string
         */
        public function insertArgs(array $args) {
            return preg_replace_callback('/\{\$(\w+)\}/', function($res) use (&$args) {
                if($args) {
                    if(array_key_exists($res[1], $args)) {
                        $value = $args[$res[1]];
                    }
                    else {
                        $value = array_shift($args);
                    }

                    if($zones = \detemiro::actions()->getZone($this->zoneName() . ".{$res[1]}.format")) {
                        foreach($zones as $action) {
                            $value = $action->make($value);
                        }
                    }

                    return $value;
                }
                else {
                    return '';
                }
            }, $this->scheme);
        }

        /**
         * Анализ запроса
         *
         * @param array $current
         *
         * @return array|null
         */
        public function analizeQuery($current) {
            $res  = array();
            $part = $this->query;

            while($part) {
                $val = array_shift($part);

                if($current) {
                    $org = array_shift($current);

                    if($val == $org) {
                        continue;
                    }
                    elseif($val = $this->analizeReg($val, $org)) {
                        $res = array_merge($res, $val);
                        continue;
                    }
                }

                return null;
            }

            return $res;
        }

        /**
         * Анализ пути
         *
         * @param array $current
         *
         * @return array|null
         */
        public function analizePath($current) {
            $res  = array();
            $part = $this->path;

            while($part) {
                $val = array_shift($part);

                if($current) {
                    if(count($part) == 0) {
                        $org = implode('/', $current);
                    }
                    else {
                        $org = array_shift($current);
                    }

                    if($val == $org) {
                        continue;
                    }
                    elseif($val = $this->analizeReg($val, $org)) {
                        $res = array_merge($res, $val);
                        continue;
                    }
                }

                return null;
            }

            return $res;
        }

        /**
         * Поиск аргументов
         *
         * @param  string $value
         * @param  string $current
         *
         * @return string
         */
        protected function analizeReg($value, $current) {
            $value = preg_replace(
                '/\{\$(\w+)\}/',
                '(?P<$1>\S+)',
                $value
            );

            preg_match('/^' . $value . '$/', $current, $res);

            return $res;
        }
    }
?>