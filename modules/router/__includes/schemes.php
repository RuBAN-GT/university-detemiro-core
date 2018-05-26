<?php
    namespace detemiro\modules\router;

    class schemes {
        /**
         * Список схем
         *
         * @var array $list
         */
        protected $list = array();
        /**
         * Получение поля $list
         *
         * @return array
         */
        public function all() {
            return $this->list;
        }

        /**
         * Обработанные ссылки
         *
         * @var array $memory
         */
        protected $memory = array();

        /**
         * Текущая ссылка
         *
         * @var \detemiro\modules\router\scheme $current
         */
        protected $current;
        /**
         * Возвращает поле $current
         *
         * @return \detemiro\modules\router\scheme
         */
        public function current() {
            return $this->current;
        }

        /**
         * Конструктор
         *
         * @param string $current Текущая ссылка
         * @param string $host    Текущий хост
         */
        public function __construct($current, $host, $default) {
            $this->list[$default]['common'] = new scheme(array(
                'scheme' => '',
                'type'   => 'common',
                'page'   => $default
            ));
            $this->list['']['common']    = new scheme(array(
                'scheme' => '?page={$key}',
                'type'   => 'common'
            ));
            $this->list['']['permalink'] = new scheme(array(
                'scheme' => '{$key}',
                'type'   => 'permalink'
            ));

            if($prot = parse_url($current, PHP_URL_SCHEME)) {
                $current = str_replace(array($prot, '://'), '', $current);
            }
            $current = trim(str_replace(array(
                $host,
                basename(\detemiro::file())
            ), '', $current), '/');

            $this->current = new scheme(array(
                'path'  => $current,
                'query' => $current
            ));
        }

        /**
         * Поиск схем
         *
         * @return int Число добавленных схем
         */
        public function scan() {
            $i = 0;

            if($schemes = \detemiro::modules()->ext()->getResult('router', '')) {
                foreach($schemes as $key=>$item) {
                    if($try = \detemiro\read_json($item['path'])) {
                        foreach($try as $page=>$part) {
                            if($page) {
                                $page = \detemiro::pages()->makeFullKey($page);
                                $part = \detemiro\take_good_array($part);

                                foreach($part as $type=>$scheme) {
                                    if($scheme && is_string($scheme)) {
                                        $scheme = new scheme(array(
                                            'type'   => $type,
                                            'page'   => $page,
                                            'scheme' => $scheme
                                        ));

                                        if(
                                            array_key_exists($page, $this->list) == false ||
                                            in_array($scheme, $this->list[$page]) == false
                                        )
                                        {
                                            $this->list[$page][$type] = $scheme;

                                            $i++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            krsort($this->list);

            return $i;
        }

        /**
         * Анализ схем
         *
         * Метод анализирует текущую ссылку на вхождение в схему.
         * В случае успеха возвращает массив с ключами page и data.
         *
         * @return \detemiro\modules\router\scheme
         */
        public function analize() {
            $save = null;

            foreach($this->list as $group) {
                foreach($group as $scheme) {
                    $tmp = array();

                    //Анализ запроса
                    if($scheme->query) {
                        if($this->current->query) {
                            $query = $scheme->analizeQuery($this->current->query);

                            if($query !== null) {
                                $tmp = array_merge($tmp, $query);
                            }
                            else {
                                continue;
                            }
                        }
                        else {
                            continue;
                        }
                    }

                    //Анализ пути
                    if($scheme->path) {
                        if($this->current->path) {
                            $path = $scheme->analizePath($this->current->path);

                            if($path !== null) {
                                $tmp = array_merge($tmp, $path);
                            }
                            else {
                                continue;
                            }
                        }
                        else {
                            continue;
                        }
                    }
                    elseif($scheme->path != $this->current->path) {
                        continue;
                    }

                    if($save == null || strlen($scheme->scheme) > strlen($save->scheme)) {
                        $new = clone $scheme;

                        $new->data = $tmp;

                        if(\detemiro::actions()->makeCheckZone($new->zoneName() . '.check', $new, $this->current) !== false) {
                            $save = $new;
                        }
                    }
                }
            }

            return $save;
        }

        /**
         * Генерации запроса по схеме
         *
         * @param string            $page   Ключ страницы
         * @param array|null        $args   Аргументы для шаблона
         * @param array|string|null $scheme Предпочитаемая схема
         *
         * @return string
         */
        public function linkAnalize($page, array $args = null, $scheme = null) {
            $hash = serialize($page) . serialize($args) . serialize($scheme);

            if(isset($this->memory[$hash])) {
                return $this->memory[$hash];
            }
            else {
                //Поиск схем
                if(isset($this->list[$page])) {
                    $schemes = $this->list[$page];
                }
                else {
                    $schemes = $this->list[''];
                }

                //Попытка подставить
                if($schemes) {
                    //Поиск схемы
                    $select = null;
                    $scheme = \detemiro\take_good_array($scheme, true);
                    while($scheme && $select == null) {
                        $try = array_shift($scheme);

                        if($try && is_string($try) && isset($schemes[$try])) {
                            $select = $schemes[$try];
                        }
                    }
                    if($select == null) {
                        $select = array_shift($schemes);
                    }

                    //Генерация ссылки
                    if($args == null) {
                        $args = array($page);
                    }

                    $this->memory[$hash] = $select->insertArgs($args);

                    return $this->memory[$hash];
                }
                else {
                    return '';
                }
            }
        }
    }
?>