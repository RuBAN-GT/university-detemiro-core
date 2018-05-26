<?php
    namespace detemiro\events;

    /**
     * Коллектор экшенов
     *
     * Данный коллектор похож на обычный, но в нём экшены могут иметь зоны, некую группу, в котором они будут отрабатываться в определённом порядке.
     */
    class actions extends collector {
        protected $object = '\detemiro\events\action';
        protected $exName = 'actions';

        /**
         * Получение экшенов, осортированных по приоритету
         * 
         * @return array
         */
        public function all() {
            if($res = parent::all()) {
                $this->sortPriority($res);
            }

            return $res;
        }

        /**
         * Зоны экшенов
         *
         * Ассоциативный массив, формирующийся по ходу добавления экшенов с зоной.
         * Данное поле позволяет быстрее находить экшены нужной зоны для makeZone().
         * 
         * @var array $zones
         */
        protected $zones = array();

        /**
         * Выполнение функции экшена
         *
         * Данный метод выполняет функцию экшена.
         * Можно добавлять аргументы, которые попадут в эту функцию.
         * 
         * @param  string $code
         *
         * @return mixed
         */
        public function make($code) {
            if($obj = $this->get($code)) {
                $res = call_user_func_array(array($obj, 'make'), array_slice(func_get_args(), 1));

                return $res;
            }
            else {
                return null;
            }
        }

        public function add(array $obj) {
            if($custom = parent::add($obj)) {
                $this->addToZone($custom->code);

                return true;
            }

            return false;
        }

        public function delete($code) {
            if($res = &$this->refGet($code)) {
                if($res->zone) {
                    $this->removeFromZone($res->zone, $code);
                }
                unset($res);
                
                return true;
            }
            else {
                return false;
            }
        }

        public function update($code, array $par) {
            if($res = parent::update($code, $par)) {
                if($res[1]->code != $res[0]->code || $res[1]->zone != $res[0]->zone) {
                    $this->removeFromZone($res[0]->zone, $res[0]->code);
                    $this->addToZone($res[1]->code);
                }

                if($res[1]->code != $res[0]->code) {
                    $this->list[$res[1]->code] = &$this->list[$res[0]->code];
                    unset($this->list[$res[0]->code]);
                }

                return true;
            }

            return false;
        }

        /**
         * Добавление экшена по коду в зону
         * 
         * @param  string $code
         *
         * @return void
         */
        protected function addToZone($code) {
            if($item = &$this->refGet($code)) {
                if($item->zone) {
                    $this->zones[$item->zone][$item->code] = &$item;
                }
            }
        }

        /**
         * Удаление экшена из зоны
         * 
         * @param  string $zone
         * @param  string $code
         *
         * @return void
         */
        protected function removeFromZone($zone, $code) {
            if(isset($this->zones[$zone][$code])) {
                unset($this->zones[$zone][$code]);

                if(count($this->zones[$zone]) == 0) {
                    unset($this->zones[$zone]);
                }
            }
        }

        /**
         * Сортировка экшенов в массиве
         * 
         * @param  array &$obj
         *
         * @return void
         */
        protected function sortPriority(array &$obj) {
            $keys = array_keys($obj);

            uasort($obj, function($a, $b) use($keys) {
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
         * Получение копий объектов экшенов из зоны
         *
         * @param  string $code
         *
         * @return array
         */
        public function getZone($code) {
            if($ex = \detemiro::modules()->ext()->getResult($this->exName, "zone.$code")) {
                $this->exMeta['zone'] = $code;

                foreach($ex as $i=>$try) {
                    $this->exMeta['source'] = $try['source'];

                    include($try['path']);

                    unset($this->exMeta['source']);
                }

                unset($this->exMeta['zone']);

                \detemiro::modules()->ext()->removeItem($this->exName, "zone.$code");
            }

            return $this->realGetZone($code);
        }

        /**
         * Получение объектов экшенов из зоны
         *
         * @param  string $code
         *
         * @return array|null
         */
        public function realGetZone($code) {
            if(isset($this->zones[$code])) {
                $this->sortPriority($this->zones[$code]);

                return $this->zones[$code];
            }

            return null;
        }

        /**
         * Выполнение экшенов из зоны
         *
         * Данный метод выполняет экшены из зоны, помещая результаты в ассоц. массив с ключом - кодом экшена.
         * Дополнительные аргументы метода попадут в функцию экшена.
         * 
         * @param  string $code Код зоны
         *
         * @return array|null
         */
        public function makeZone($code) {
            if($zone = $this->getZone($code)) {
                $res  = array();
                $args = array_slice(func_get_args(), 1);

                foreach($zone as $obj) {
                    $res[$obj->code] = call_user_func_array(array($obj, 'make'), $args);
                }

                return $res;
            }
            else {
                return null;
            }
        }

        /**
         * Выполнение проверочных экшенов из зоны
         * Результатом является false, true или null, если не существует обработчиков.
         *
         * @param  string $code Код зоны
         *
         * @return bool|null
         */
        public function makeCheckZone($code) {
            if($zone = $this->getZone($code)) {
                $res  = true;
                $args = array_slice(func_get_args(), 1);

                while($zone && $res) {
                    $obj = array_shift($zone);

                    if(call_user_func_array(array($obj, 'make'), $args) === false) {
                        $res = false;
                    }
                }

                return $res;
            }
            else {
                return null;
            }
        }

        /**
         * Получение событий из файла с возможным присвоением зоны
         *
         * @param  string $file
         * @param  string $code Возможная зона
         *
         * @return array|null
         */
        public function getFileZone($file, $code = null) {
            if($code == null) {
                $code = 'unnamed-' . \detemiro\random_hash(10, false);
            }

            if(file_exists($file)) {
                $this->exMeta['zone']   = $code;
                $this->exMeta['source'] = $file;

                include($file);

                unset($this->exMeta['zone'], $this->exMeta['source']);

                return $this->getZone($code);
            }

            return null;
        }

        /**
         * Выполнение событий из файла с возможным присвоением зоны
         *
         * @param string $file
         * @param string $code Возможная зона
         *
         * @return array|null
         */
        public function makeFileZone($file, $code = null) {
            if($zone = $this->getFileZone($file, $code)) {
                $res  = array();
                $args = array_slice(func_get_args(), 2);

                foreach($zone as $obj) {
                    $res[$obj->code] = call_user_func_array(array($obj, 'make'), $args);
                }

                return $res;
            }
            else {
                return null;
            }
        }

        /**
         * Выполнение проверочных экшенов из файла по зоне
         *
         * @param string $file
         * @param string $code Возможная зона
         *
         * @return bool|null
         */
        public function makeCheckFileZone($file, $code = null) {
            if($zone = $this->getFileZone($file, $code)) {
                $res  = true;
                $args = array_slice(func_get_args(), 2);

                while($zone && $res) {
                    $obj = array_shift($zone);

                    if(call_user_func_array(array($obj, 'make'), $args) === false) {
                        $res = false;
                    }
                }

                return $res;
            }
            else {
                return null;
            }
        }
    }
?>