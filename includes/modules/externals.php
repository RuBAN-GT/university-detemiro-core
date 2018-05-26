<?php
    namespace detemiro\modules;

    /**
     * Класс-коллектор подгрузок с именами
     */
    class externals {
        /**
         * Найденые файлы в дополнительных директориях модулей
         *
         * @var array $list
         */
        protected $list = array();

        /**
         * Возвращает поле $exScans
         *
         * @return array
         */
        public function all() {
            return $this->list;
        }

        /**
         * Добавление категории для дополнительных директорий
         *
         * @param  string $name
         *
         * @return void
         */
        public function addType($name) {
            if($name && array_key_exists($name, $this->list) == false) {
                $this->list[$name]     = array();
            }
        }

        /**
         * Удаление категории
         *
         * @param  string $name
         *
         * @return void
         */
        public function removeType($name) {
            if(array_key_exists($name, $this->list)) {
                unset($this->list[$name]);
            }
        }

        /**
         * Получение всех элементов в дополнительно просканированных директориях по типу
         *
         * @param  string     $type Тип директории
         *
         * @return array|null
         */
        public function getType($type) {
            if(isset($this->list[$type])) {
                return $this->list[$type];
            }
            else {
                return null;
            }
        }

        /**
         * Получение найденого элемента в дополнительно просканированных директориях
         *
         * @param  string $type Тип директории
         * @param  string $item Имя элемента
         *
         * @return array|null
         */
        public function getResult($type, $item = '') {
            if(isset($this->list[$type][$item])) {
                return $this->list[$type][$item];
            }
            else {
                return null;
            }
        }

        /**
         * Удаление записи об элементе
         *
         * @param string     $type Тип директории
         * @param string     $item Имя элемента
         *
         * @return bool
         */
        public function removeItem($type, $item) {
            if(isset($this->list[$type][$item])) {
                unset($this->list[$type][$item]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Удаление записи о файле
         *
         * @param string     $type Тип директории
         * @param string     $item Имя элемента
         * @param string|int $i    Индекс файла
         *
         * @return bool
         */
        public function removeFile($type, $item, $i) {
            if(isset($this->list[$type][$item][$i])) {
                unset($this->list[$type][$item][$i]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Сканирование директории на наличие дополнительных директорий
         *
         * @param  string $path   Путь до директории
         * @param  string $source Название источника
         *
         * @return int Количество добавленных файлов
         */
        public function scan($path, $source = null) {
            $i = 0;

            $path = \detemiro\norm_path($path);

            if($need = array_keys($this->list)) {
                if(is_dir($path)) {
                    foreach(new \DirectoryIterator($path) as $main) {
                        if($main->isDir()) {
                            $type = $main->getFileName();

                            if(in_array($type, $need)) {
                                foreach(new \DirectoryIterator($main->getPathname()) as $item) {
                                    if($item->isDot()) {
                                        continue;
                                    }
                                    elseif($item->isFile()) {
                                        $data = array(
                                            'path'   => $item->getPathname(),
                                            'source' => $source
                                        );
                                        $name = $item->getBasename('.' . $item->getExtension());

                                        if(
                                            array_key_exists($name, $this->list[$type]) == false ||
                                            in_array($data, $this->list[$type][$name]) == false
                                        )
                                        {
                                            $this->list[$type][$name][] = $data;

                                            $i++;
                                        }
                                    }
                                }
                            }
                        }
                        elseif($main->isFile()) {
                            $name = $main->getBasename('.' . $main->getExtension());

                            if(in_array($name, $need)) {
                                $data = array(
                                    'path'   => $main->getPathname(),
                                    'source' => $source
                                );

                                if(
                                    array_key_exists('', $this->list[$name]) == false ||
                                    in_array($data, $this->list[$name]['']) == false
                                )
                                {
                                    $this->list[$name][''][] = $data;

                                    $i++;
                                }
                            }
                        }
                    }
                }
            }

            return $i;
        }
    }
?>