<?php
    namespace detemiro\modules\pages;

    /**
     * Коллектор страниц
     */
    class pages extends \detemiro\events\collector {
        protected $object = '\detemiro\modules\pages\page';
        protected $exName = 'pages';

        /**
         * Дочерные элементы, которые ещё не имеют родителя
         * 
         * @var array $childs
         */
        protected $childs = array();

        /**
         * Формирование ключа для страниц
         * 
         * @param  array|string $bread
         *
         * @return string|null
         */
        public function makeFullKey($bread) {
            if(is_string($bread)) {
                return str_replace('/', '-', $bread);
            }
            elseif(is_array($bread)) {
                return implode('-', $bread);
            }
            else {
                return null;
            }
        }

        /**
         * Формирование обратной строки по ключи (со слешами)
         *
         * @param  string $key
         *
         * @return string|null
         */
        public function makeBackKey($key) {
            if($page = $this->get($key)) {
                $res = array($page->code);

                while($page->parent) {
                    if($try = $this->get($page->parent)) {
                        $page = $try;
                        array_unshift($res, $page->code);
                    }
                    else {
                        array_unshift($res, $page->parent);
                        break;
                    }
                }

                return implode('/', $res);
            }
            else {
                return null;
            }
        }

        /**
         * Получение копии объекта страницы
         *
         * Данный метод возвращает копию объекта страницы по её ключу, если она существует.
         * Если же страницы не существует, то система пытается её найти в файлах модуля, подключая подходящие.
         * 
         * Аргумент попадает в makeFullKey.
         *
         * @see \detemiro\events\pages::makeFullKey()
         * 
         * @param  array|string
         *
         * @return $object
         */
        public function get($code) {
            $key = $this->makeFullKey($code);

            if(isset($this->list[$key])) {
                return clone $this->list[$key];
            }
            elseif($ex = \detemiro::modules()->ext()->getResult($this->exName, $key)) {
                $this->exMeta['code'] = $key;

                foreach(array_reverse($ex) as $i=>$try) {
                    $this->exMeta['source'] = $try['source'];

                    include($try['path']);
                    \detemiro::modules()->ext()->removeFile($this->exName, $key, $i);

                    unset($this->exMeta['source']);

                    if(isset($this->list[$key])) {
                        unset($this->exMeta['code']);

                        return clone $this->list[$key];
                    }
                }

                unset($this->exMeta['code']);
            }

            return null;
        }

        /**
         * Получение копии объекта страницы
         *
         * Данный метод возвращает копию объекта страницы по её ключу, если она существует.
         * Аргумент попадает в makeFullKey.
         *
         * @see \detemiro\events\pages::makeFullKey()
         * 
         * @param  array|string
         *
         * @return $object
         */
        public function realGet($code) {
            $key = $this->makeFullKey($code);

            return (isset($this->list[$key])) ? clone $this->list[$key] : null;
        }

        public function exists($code) {
            $key = $this->makeFullKey($code);

            if(isset($this->list[$key]) || \detemiro::modules()->ext()->getResult($this->exName, $key)) {
                return true;
            }
            else {
                return false;
            }
        }

        public function realExists($code) {
            return (isset($this->list[$this->makeFullKey($code)]));
        }

        public function add(array $obj) {
            if($this->exMeta) {
                $copy = array_replace($obj, $this->exMeta);

                if(isset($obj['code']) && $copy['code'] != $obj['code']) {
                    $copy['code'] = $obj['code'];
                }
            }
            else {
                $copy = $obj;
            }

            try {
                $custom = new $this->object($copy);
            }
            catch(\Exception $error) {
                \detemiro::messages()->push(array(
                    'title'  => 'Ошибка добавления страницы',
                    'status' => 'warning',
                    'type'   => 'system',
                    'text'   => "Не удаётся добавить страницу [{$error->getMessage()}]."
                ));

                return false;
            }

            if($this->realExists($custom->key) == false && (\detemiro::space()->code == $custom->space)) {
                /**
                 * Добавление страницы
                 */
                $this->list[$custom->key] = $custom;

                /**
                 * Проверка наследников
                 */
                $this->list[$custom->key]->childs = $this->checkChilds($custom->key);

                /**
                 * Добавление к родителю
                 */
                $this->pushToParent($custom->key);

                return true;
            }

            return false;
        }

        public function update($code, array $par) {
            $key = $this->makeFullKey($code);
            
            if(array_key_exists('childs', $par)) {
                unset($par['childs']);
            }

            if($res = parent::update($key, $par)) {
                if($res[0]->key != $res[1]->key) {
                    $this->removeFromAll($res[0]->key);

                    $this->list[$res[1]->key] = &$this->list[$res[0]->key];
                    unset($this->list[$res[0]->key]);

                    $res[1]->childs = $this->checkChilds($res[1]->key);

                    $this->pushToParent($res[1]->key);
                }

                return true;
            }

            return false;
        }

        public function set(array $par) {
            if(array_key_exists('code', $par)) {
                $key = $this->makeFullKey($par['code']);

                if(isset($this->list[$key])) {
                    return $this->update($key, $par);
                }
            }

            return $this->add($par);
        }

        public function delete($code) {
            $key = $this->makeFullKey($code);

            if($this->exists($key)) {
                $this->removeFromAll($key);

                if($this->list[$key]->childs) {
                    $this->childs[$key] = $this->list[$key]->childs;
                }

                unset($this->list[$key]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Выполнение метода show у объекта страницы
         *
         * @see \detemiro\events\page::show()
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function show($code) {
            $key = $this->makeFullKey($code);

            if($page = $this->get($key)) {
                call_user_func_array(array($page, 'show'), array_slice(func_get_args(), 1));

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Проверка наследников
         * 
         * @param  string     $key
         *
         * @return array
         */
        protected function checkChilds($key) {
            if($obj = &$this->refGet($key)) {
                if(isset($this->childs[$obj->key])) {
                    $copy = $this->childs[$obj->key];

                    unset($this->childs[$obj->key]);

                    return $copy; 
                }
            }

            return array();
        }

        /**
         * Добавление к родителю
         * 
         * @param  string $key
         *
         * @return void
         */
        protected function pushToParent($key) {
            if($obj = &$this->refGet($key)) {
                if($obj->parent) {
                    if($parent = &$this->refGet($obj->parent)) {
                        $parent->childs[$obj->code] = $obj;
                    }
                    else {
                        $this->childs[$obj->parent][$obj->code] = $obj;
                    }
                }
            }
        }

        /**
         * Очистка наследников
         * 
         * @param  string $key
         *
         * @return void
         */
        protected function removeFromAll($key) {
            if($obj = &$this->refGet($key)) {
                if($obj->childs) {
                    $this->childs[$obj->key] = $obj->childs;
                }

                if($obj->parent) {
                    if($parent = &$this->refGet($obj->parent)) {
                        unset($parent->childs[$obj->code]);
                    }
                    else {
                        unset($this->childs[$obj->parent][$obj->code]);

                        if(count($this->childs[$obj->parent]) == 0) {
                            unset($this->childs[$obj->parent]);
                        }
                    }
                }
            }
        }
    }
?>