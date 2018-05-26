<?php
    namespace detemiro\modules\navigation;

    /**
     * Класс навигации
     */
    class nav {
        /**
         * Объект элемента
         *
         * @var string $object
         */
        protected $object = '\detemiro\modules\navigation\item';

        /**
         * Список элементов
         *
         * @var array $list
         */
        protected $list = array();
        public function getSortedList() {
            $this->sortPriorityAll();

            return $this->list;
        }

        /**
         * Дочерные элементы, которые ещё не имеют родителя
         *
         * @var array $childs
         */
        protected $childs = array();

        /**
         * Конструктор
         *
         * @throws \Exception Если задан неверный класс объекта элемента навигации.
         */
        public function __construct() {
            if(
                $this->object == '' ||
                is_string($this->object) == false ||
                class_exists($this->object) == false &&
                \detemiro::modules()->classLoader($this->object) == false ||
                is_subclass_of($this->object, '\detemiro\modules\navigation\item') == false &&
                $this->object != '\detemiro\modules\navigation\item'
            )
            {
                throw new \Exception('Неверный класс объекта элемента навигации.');
            }
        }

        /**
         * Проверка элемента на существование
         *
         * @param  string $code
         *
         * @return bool
         */
        public function exists($code) {
            return (isset($this->list[$code]));
        }

        /**
         * Добавление элемента
         *
         * @param  array $item
         *
         * @return string|false Новый код добавленного элемента
         */
        public function push(array $item) {
            $item = new $this->object($item);

            if($item->code == '' && $item->value) {
                $item->code = md5($item->parent . $item->value);
            }

            if($this->exists($item->code)) {
                return false;
            }
            else {
                $this->list[$item->code] = $item;

                $this->list[$item->code]->childs = $this->checkChilds($item->code);
                $this->pushToParent($item->code);

                return $item->code;
            }
        }

        /**
         * Установка элемента
         *
         * @param  array $item
         *
         * @return bool|string
         */
        public function set(array $item) {
            if(isset($item['code']) && $this->exists($item['code'])) {
                return $this->update($item['code'], $item);
            }
            else {
                return $this->push($item);
            }
        }

        /**
         * Получение элемента
         *
         * @param  string $code
         *
         * @return bool
         */
        public function get($code) {
            if($this->exists($code)) {
                return clone $this->list[$code];
            }
            else {
                return false;
            }
        }

        /**
         * Обновление элемента
         *
         * @param  string $code
         * @param  array  $item
         *
         * @return bool
         */
        public function update($code, array $item) {
            if($this->exists($code)) {
                if(isset($item['childs'])) {
                    unset($item['childs']);
                }

                $old = clone $this->list[$code];
                $new = $this->list[$code];

                $new->update($item);

                if($new->code != $old->code) {
                    $this->removeFromAll($old->code);

                    $this->list[$new->code] = &$this->list[$old->code];
                    unset($this->list[$old->code]);

                    $new->childs = $this->checkChilds($new->code);

                    $this->pushToParent($new->code);

                    return true;
                }
            }
            else {
                return false;
            }
        }

        /**
         * Удаление элемента
         *
         * @param $code
         *
         * @return bool
         */
        public function delete($code) {
            if($this->exists($code)) {
                $this->removeFromAll($code);

                if($this->list[$code]->childs) {
                    $this->childs[$code] = $this->list[$code]->childs;
                }

                unset($this->list[$code]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Экспорт навигации в JSON
         *
         * @return string
         */
        public function exportList() {
            return \detemiro\json_val_encode($this->list);
        }

        /**
         * Экспорт элементов навигации в JSON
         *
         * @return string
         */
        public function export() {
            return \detemiro\json_val_encode($this->list);
        }

        /**
         * Импорт элементов навигации
         *
         * @param array|string $list
         */
        public function importList($list) {
            if(is_string($list)) {
                $list = \detemiro\json_decode_struct($list, true);
            }

            if($list && is_array($list)) {
                foreach($list as $item) {
                    if(is_array($item)) {
                        $this->push($item);
                    }
                }
            }
        }

        /**
         * Импорт навигации
         *
         * @param array|string $nav
         */
        public function import($nav) {
            if(is_string($nav)) {
                $nav = \detemiro\json_decode_struct($nav);
            }

            if(isset($nav['code'])) {
                $this->updateCode($nav['code']);
            }
            if(isset($nav['info'])) {
                $this->updateInfo($nav['info']);
            }
            if(isset($nav['list'])) {
                $this->importList($nav['list']);
            }
        }

        /**
         * Сортировка элеметов в списке
         */
        public function sortPriorityAll() {
            $this->sortPriority($this->list);
        }
        protected function sortPriority(&$obj) {
            ksort($obj);

            $keys = array_keys($obj);

            uasort($obj, function($a, $b) use($keys) {
                if($a->childs) {
                    $this->sortPriority($a->childs);
                }
                if($b->childs) {
                    $this->sortPriority($b->childs);
                }

                if($a->priority === $b->priority) {
                    $i = array_search($a->name, $keys);
                    $j = array_search($b->name, $keys);

                    return ($i >= $j);
                }
                elseif(is_numeric($a->priority) && is_numeric($b->priority)) {
                    return ($a->priority >= $b->priority);
                }
                elseif($a->priority === false || $a->priority === null || $b->priority === true) {
                    return -1;
                }
                elseif($b->priority === false || $b->priority === null || $a->priority === true) {
                    return 1;
                }
                else {
                    return 0;
                }
            });
        }

        /**
         * Проверка наследников
         *
         * @param  string     $code
         *
         * @return array
         */
        protected function checkChilds($code) {
            if($this->exists($code)) {
                $obj = &$this->list[$code];

                if(isset($this->childs[$obj->code])) {
                    $copy = $this->childs[$obj->code];

                    unset($this->childs[$obj->code]);

                    return $copy;
                }
            }

            return array();
        }

        /**
         * Добавление к родителю
         *
         * @param  string $code
         *
         * @return void
         */
        protected function pushToParent($code) {
            if($this->exists($code)) {
                $obj = &$this->list[$code];

                if($obj->parent) {
                    if($this->exists($obj->parent)) {
                        $this->list[$obj->parent]->childs[$obj->code] = $obj;
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
         * @param  string $code
         *
         * @return void
         */
        protected function removeFromAll($code) {
            if($this->exists($code)) {
                $obj = &$this->list[$code];

                if($obj->childs) {
                    $this->childs[$obj->code] = $obj->childs;
                }

                if($obj->parent) {
                    if($this->exists($obj->parent)) {
                        unset($this->list[$obj->parent]->childs[$obj->code]);
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