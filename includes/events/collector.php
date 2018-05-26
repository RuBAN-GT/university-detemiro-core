<?php
    namespace detemiro\events;

    /**
     * Коллектор событий
     *
     * @throws \Exception Если указан неверный тип объекта.
     */
    class collector {
        /**
         * Коллектор событий
         * 
         * @var array $list
         */
        protected $list = array();

        /**
         * Получение $list с копиями объектов
         * 
         * @return array
         */
        public function all() {
            $res = array();

            foreach($this->list as $key=>$object) {
                $res[$key] = clone $object;
            }

            return $res;
        }

        /**
         * Получение $list с копиями объектов и всеми подключениями
         * 
         * @return array
         */
        public function fullAll() {
            if($all = \detemiro::modules()->ext()->getType($this->exName)) {
                foreach($all as $key=>$family) {
                    $this->exMeta['code'] = $key;

                    foreach($family as $i=>$item) {
                        $this->exMeta['source'] = $item['source'];

                        include($item['path']);
                        \detemiro::modules()->ext()->removeFile($this->exName, $key, $i);

                        unset($this->exMeta['source']);
                    }

                    unset($this->exMeta['code']);
                }
            }

            return $this->all();
        }

        /**
         * Тип объекта события
         * 
         * @var string $object
         */
        protected $object = '\detemiro\events\object';

        /**
         * Имя дополнительной директории для модулей
         * 
         * @var string $exName
         */
        protected $exName;

        /**
         * Временная информация для объектов из дополнительных директорий
         * 
         * @var array $exMeta
         */
        protected $exMeta = array();

        /**
         * Получение ссылки на объект
         * 
         * @param  string $key
         *
         * @return $object|null
         */
        protected function &refGet($key) {
            if(isset($this->list[$key])) {
                return $this->list[$key];
            }
            else {
                return $null;
            }
        }

        /**
         * Добавление объекта в коллектор
         *
         * Данный метод добавляет объект в коллектор, в качестве ключей массива следуюет указать поля будущего объекта.
         * 
         * @param  array $obj
         *
         * @return bool
         */
        public function add(array $obj) {
            if($this->exMeta) {
                $obj = array_replace($obj, $this->exMeta);
            }

            if($module = \detemiro::modules()->current()) {
                $obj['source'] = $module->code;
            }

            try {
                $custom = new $this->object($obj);
            }
            catch(\Exception $error) {
                \detemiro::messages()->push(array(
                    'title'  => 'Ошибка добавления объекта',
                    'status' => 'system',
                    'text'   => "Не удалось создать объект [{$error->getMessage()}].",
                    'type'   => str_replace(__NAMESPACE__ . '\\', '', get_called_class())
                ));

                return false;
            }

            if($this->realExists($custom->code) == false && (\detemiro::space()->code == $custom->space)) {
                $this->list[$custom->code] = $custom;

                return $custom;
            }

            return false;
        }

        /**
         * Обновление объекта
         *
         * Данный метод обновляет уже существующий объект.
         * В качестве ключей второго аргумента следует указать поля этого объекта.
         * 
         * @param  string $key
         * @param  array  $par
         *
         * @return bool
         */
        public function update($key, array $par) {
            $mod = null;

            if($par && ($mod = &$this->refGet($key))) {
                $old = clone $mod;

                if(array_key_exists('function', $par)) {
                    if($module = \detemiro::modules()->current()) {
                        $par['source'] = $module->code;
                    }
                }

                $code  = (isset($par['code'])) ? $par['code'] : null;
                $space = (isset($par['space'])) ? $par['space'] : null;

                if((!$code || $old->code == $code || !isset($this->list[$code])) && (!$space || $space == 'local' || $space == $mod->space)) {
                    foreach($par as $key=>$value) {
                        $mod->$key = $value;
                    }

                    return array($old, $mod);
                }
            }

            return false;
        }

        /**
         * Вставка объекта
         *
         * Данный метод добавляет объект в коллектор, если его нет, и обновляет, в противном случае.
         * 
         * @param  array $par
         *
         * @return bool
         */
        public function set(array $par) {
            if(array_key_exists('code', $par) && isset($this->list[$par['code']])) {
                return $this->update($par['code'], $par);
            }
            else {
                return $this->add($par);
            }
        }

        /**
         * Получение копии объекта
         *
         * Данный метод возвращает копию объекта по его коду, если он существует.
         * Если же объекта не существует, то система пытается найти его в файлах модуля, подключая подходящие.
         * 
         * @param  string $code
         *
         * @return $object|null
         */
        public function get($code) {
            if(isset($this->list[$code])) {
                return clone $this->list[$code];
            }
            elseif($ex = \detemiro::modules()->ext()->getResult($this->exName, $code)) {
                $this->exMeta['code'] = $code;

                foreach(array_reverse($ex) as $i=>$try) {
                    $this->exMeta['source'] = $try['source'];

                    include($try['path']);
                    \detemiro::modules()->ext()->removeFile($this->exName, $code, $i);

                    unset($this->exMeta['source']);

                    if(isset($this->list[$code])) {
                        unset($this->exMeta['code']);

                        return clone $this->list[$code];
                    }
                }

                unset($this->exMeta['code']);
            }

            return null;
        }

        /**
         * Получение копии объекта
         * 
         * @param  string $code
         *
         * @return $object|null
         */
        public function realGet($code) {
            return (isset($this->list[$code])) ? clone $this->list[$code] : null;
        }

        /**
         * Проверка существования объекта
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function exists($code) {
            if(isset($this->list[$code]) || \detemiro::modules()->ext()->getResult($this->exName, $code)) {
                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Проверка существования объекта в памяти
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function realExists($code) {
            return (isset($this->list[$code]));
        }

        /**
         * Удаление объекта из коллектора
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function delete($code) {
            if(isset($this->list[$code])) {
                unset($this->list[$code]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Выполнение функции объекта
         * 
         * @param  string $code
         *
         * @return mixed
         */
        protected function doit($code) {
            if($obj = $this->get($code)) {
                return call_user_func_array(array($obj, 'doit'), array_slice(func_get_args(), 1));
            }
            else {
                return null;
            }
        }

        /**
         * Инициализация коллектора
         *
         * @throws \Exception Если выбран неверный класс объекта.
         */
        public function __construct() {
            if(
                (
                    $this->object && is_string($this->object) &&
                    class_exists($this->object)
                    ||
                    \detemiro::modules()->classLoader($this->object)
                ) &&
                (
                    is_subclass_of($this->object, '\detemiro\events\object') ||
                    $this->object == '\detemiro\events\object'
                )
            )
            {
                if($this->exName == '') {
                    $this->exName = explode('\\', $this);
                    $this->exName = end($this->exName);
                }
            }
            else {
                throw new \Exception('Неверный класс объекта.');
            }
        }
    }
?>