<?php
    namespace detemiro\modules;

    /**
     * Класс зависимостей
     */
    class relationsCollector {
        /**
         * Массив, содержащий объекты отношений
         *
         * @var array $rels
         */
        protected $rels = array();

        /**
         * Массив, содержащий объекты отношений по типам
         *
         * @var array $types
         */
        protected $types = array();

        /**
         * Разрешённые методы зависимостей
         *
         * @return array
         */
        public static function allows() {
            return array(
                'require',
                'avoid',
                'support'
            );
        }

        /**
         * Получение всех зависимостей
         *
         * @return array
         */
        public function dump() {
            $res = array();

            foreach($this->rels as $item) {
                $res[] = clone $item;
            }

            return $res;
        }

        /**
         * Получение зависимостей по типу
         *
         * @param  string $type
         *
         * @return array
         */
        public function dumpType($type = '') {
            $res = array();

            if(isset($this->types[$type])) {
                foreach($this->types[$type] as $item) {
                    $res[] = clone $item;
                }
            }

            return $res;
        }

        /**
         * Получение зависимостей по методу
         *
         * @param  string $method
         *
         * @return array
         */
        public function dumpMethod($method) {
            $res = array();

            foreach($this->rels as $item) {
                if($item->method == $method) {
                    $res[] = clone $item;
                }
            }

            return $res;
        }

        /**
         * Добавление зависимости
         *
         * @see \detemiro\modules\relation
         *
         * @param  array  $relation Массив должен содержать ключи `method` и `name`
         * @param  string $type
         *
         * @return bool
         */
        public function push(array $relation, $type = '') {
            if(is_string($type)) {
                try {
                    $obj = new relation($relation, $type);

                    $key = "{$obj->method}.{$obj->name}";
                    if(array_key_exists($key, $this->rels) == false) {
                        $this->rels[$key] = $obj;
                    }
                    if(isset($this->types[$type]) == false || array_key_exists($key, $this->types[$type]) == false) {
                        $this->types[$type][$key] = &$this->rels[$key];
                    }

                    return true;
                }
                catch(\Exception $error) {
                    return false;
                }
            }
            else {
                return false;
            }
        }

        /**
         * Массовое добавление зависимостей
         *
         * @param  array  $data
         * @param  string $type
         *
         * @return int Число успешно добавленных зависимостей
         */
        public function pack(array $data, $type = '') {
            $i = 0;

            if($data) {
                if(\detemiro\is_assoc_array($data)) {
                    $data = array($data);
                }

                foreach($data as $item) {
                    if($this->push($item, $type)) {
                        $i++;
                    }
                }
            }

            return $i;
        }

        /**
         * Проверка зависимости по типу
         *
         * @param  string $type
         *
         * @return bool
         */
        public function check($type = '') {
            if(isset($this->types[$type])) {
                uasort($this->types[$type], function($a, $b) {
                    return ($a->method == 'avoid' && $b->method != 'avoid') ? 1 : 0;
                });

                $rels = $this->types[$type];

                foreach($rels as $key=>&$item) {
                    if($item->status === null) {
                        $tmp = false;

                        if($module = \detemiro::modules()->get($item->name)) {
                            if($module->method == 'avoid') {
                                if($module->run < 1) {
                                    $tmp = true;
                                }
                            }
                            else {
                                if($module->run >= 1) {
                                    $tmp = true;
                                }
                                elseif($module->run == 0) {
                                    if(\detemiro::modules()->status($item->name) < 2) {
                                        $tmp = \detemiro::modules()->activate($item->name);
                                    }
                                    else {
                                        $tmp = true;
                                    }

                                    if($tmp) {
                                        $tmp = \detemiro::modules()->run($item->name);
                                    }
                                }
                            }
                        }

                        if($tmp) {
                            $item->status = true;
                        }
                        elseif($tmp == false && $item->method != 'support') {
                            $item->status = false;

                            return false;
                        }
                    }
                    elseif(
                        $item->status === false &&
                        $item->method != 'support'
                    ) {
                        $item->status = false;

                        return false;
                    }

                    if($item->status) {
                        unset($this->types[$type][$key]);
                    }
                }
            }

            return true;
        }
    }
?>