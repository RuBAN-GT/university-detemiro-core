<?php
    namespace detemiro\modules\database;

    /**
     * Класс-обёртка для манипуляции с данными из БД
     *
     * @throws \Exception Если не существует таблицы.
     */
    class data {
        /**
         * Объект для работы с БД
         *
         * @var \detemiro\modules\database\dbActions
         */
        protected $db;

        /**
         * Префикс таблиц в БД
         *
         * @return string
         */
        public function prefix() {
            return $this->db->prefix;
        }

        /**
         * Таблица контента
         *
         * Поле таблицы контента. Стандартное значение - имя класса.
         *
         * @var string $table
         */
        protected $table;
        public function table() {
            return $this->table;
        }

        /**
         * Структура таблицы
         *
         * @var array $struct
         */
        protected $struct = array();
        public function struct() {
            return $this->struct;
        }

        /**
         * Массив первичных ключей
         *
         * @var array $primary
         */
        protected $primary = array('id');
        public function primary() {
            return $this->primary;
        }

        /**
         * Выражение, содержащие ID.
         *
         * @var string $sequence
         */
        protected $sequence;
        public function sequence() {
            return $this->sequence;
        }

        protected function getPrepare(&$values, &$keys) {
            if($keys == '') {
                $keys = $this->primary;
            }
            elseif(is_string($keys)) {
                $keys = \detemiro\take_good_array($keys, true);
            }
            else {
                return false;
            }

            if($values = \detemiro\take_good_array($values)) {
                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Генерация ассоциативного массива: ключ условия -> значение.
         *
         * @param  mixed        $values
         * @param  array|string $keys
         *
         * @return array|null
         */
        public function getParam($values, $keys = '') {
            if($this->getPrepare($value, $keys)) {
                $main = array();

                if(\detemiro\is_assoc_array($values)) {
                    foreach($keys as $key) {
                        if(array_key_exists($key, $values) && $this->__torchCheck($key, $values[$key])) {
                            $main[$key] = $values[$key];
                        }
                        else {
                            return null;
                        }
                    }
                }
                else {
                    foreach($keys as $key) {
                        if($values) {
                            $value = array_shift($values);

                            if($this->__torchCheck($key, $value)) {
                                $main[$key] = $value;
                            }
                            else {
                                return null;
                            }
                        }
                        else {
                            return null;
                        }
                    }
                }

                return $main;
            }

            return null;
        }

        /**
         * Генерация услови по ключам из $keys или первичным ключам со значениями $values
         *
         * @param  mixed        $values
         * @param  array|string $keys
         *
         * @return array|null
         */
        public function getCond($values, $keys = '') {
            if($this->getPrepare($values, $keys)) {
                $main = array();

                if(\detemiro\is_assoc_array($values)) {
                    foreach($keys as $key) {
                        if(array_key_exists($key, $values) && $this->__torchCheck($key, $values[$key])) {
                            $main[] = array(
                                'param' => $key,
                                'value' => $values[$key]
                            );
                        }
                        else {
                            return null;
                        }
                    }
                }
                else {
                    foreach($keys as $key) {
                        if($values) {
                            $value = array_shift($values);

                            if($this->__torchCheck($key, $value)) {
                                $main[] = array(
                                    'param' => $key,
                                    'value' => $value
                                );
                            }
                            else {
                                return null;
                            }
                        }
                        else {
                            return null;
                        }
                    }
                }

                return $main;
            }

            return null;
        }

        /**
         * Инициализация
         *
         * @param \detemiro\modules\database\dbActions $db Объект для работы с БД
         *
         * @throws \Exception Если не существует таблицы.
         */
        public function __construct(\detemiro\modules\database\dbActions $db) {
            $this->db = $db;

            if($this->table == null) {
                $this->table = (new \ReflectionClass($this))->getShortName();
            }

            $this->primary = \detemiro\take_good_array($this->primary, true);

            if($struct = $this->db->struct($this->table)) {
                $this->struct = $struct;
            }
            else {
                throw new \Exception("Не существует необходимой таблицы {$this->db->prefix}{$this->table}");
            }
        }

        /**
         * Количество элементов при условии
         *
         * @see \detemiro\modules\database\dbActions::count()
         *
         * @param  array|null $cond Условие
         *
         * @return int
         */
        public function count(array $cond = null) {
            return $this->db->count($this->table, $cond);
        }

        /**
         * Получение элементов (как select)
         *
         * @see \detemiro\modules\database\dbActions::select()
         * 
         * @param  array  $par
         *
         * @return mixed
         */
        public function get(array $par = array()) {
            $par['table'] = $this->table;

            return $this->db->select($par);
        }

        /**
         * Получение полных элементов, пройденных через обработчик __getHandler
         *
         * @param array $par
         *
         * @return array|null
         */
        public function getItems(array $par = array()) {
            $custom = array(
                'limit'  => null,
                'offset' => null,
                'order'  => null,
                'cond'   => null
            );

            if($par) {
                $custom = array_merge($custom, array_intersect_key($par, $custom));
            }

            if($res = $this->get($custom)) {
                foreach($res as &$item) {
                    $this->__getHandler($item);
                }

                return $res;
            }

            return null;
        }

        /**
         * Получение одного элемента, пройдённого через обработчик __getHandler
         *
         * @see \detemiro\modules\database\dbActions::select()
         * 
         * @param  mixed      $primary
         *
         * @return array|null
         */
        public function getItem($primary) {
            if($param = $this->getCond($primary)) {
                if($res = $this->db->select(array(
                    'table'  => $this->table,
                    'cols'   => '*',
                    'oneRow' => 0,
                    'cond'   => $param
                ))) {
                    $this->__getHandler($res);

                    return $res;
                }
            }

            return null;
        }

        /**
         * Добавление элемента
         *
         * Добавление происходит в несколько стадий:
         * * Метод __addBeforeHandler проверяет аргументы, если он не выдаёт false, то далее.
         * * Происходит вставка данных.
         * * В случае успеха вызывается __addSuccessHandler, если он выдаёт false, то вставка отменяется.
         * * В случае провала вызывается __addFailHandler.
         *
         * @see \detemiro\modules\database\dbActions::insert()
         * 
         * @param  array $par
         *
         * @return int|true ID новой записи или true, если используется нестандартный первичный ключ.
         */
        public function add(array $par) {
            if($this->__addBeforeHandler($par) !== false) {
                $this->db->beginTransaction();

                if($this->db->insert($this->table, $par)) {
                    if($this->__addSuccessHandler($par) === false) {
                        $this->db->rollBack();
                    }
                    else {
                        $res = $this->lastInsertId();
                        if($res == '0') {
                            $res = true;
                        }

                        $this->db->commit();

                        return $res;
                    }
                }
                else {
                    $this->db->rollBack();
                }
            }

            $this->__addFailHandler($par);

            return false;
        }

        /**
         * Получение последнего ID добавленного пользователя
         *
         * @return int
         */
        public function lastInsertId() {
            return $this->db->lastInsertId($this->sequence);
        }

        /**
         * Проверка существования элемента
         * 
         * @param  mixed $primary
         *
         * @return bool
         */
        public function exists($primary) {
            if($param = $this->getCond($primary)) {
                $res = $this->db->count($this->table, $param);

                return ($res > 0);
            }
            else {
                return false;
            }
        }

        /**
         * Удаление элементов по условию
         *
         * @see \detemiro\modules\database\dbActions::delete()
         *
         * @param  array  $cond Условие
         *
         * @return bool
         */
        public function delete(array $cond) {
            return $this->db->delete($this->table, $cond);
        }

        /**
         * Удаление элемента
         *
         * @see \detemiro\modules\database\dbActions::delete()
         * 
         * @param  mixed $primary
         *
         * @return bool
         */
        public function deleteItem($primary) {
            if($param = $this->getCond($primary)) {
                return $this->delete($param);
            }
            else {
                return false;
            }
        }

        /**
         * Обновление элементов
         * Обновление происходит в несколько этапов:
         * * Вызывается метод __updateBeforeHandler, если он не выдает false, то далее.
         * * Происходит обновление данных
         * * Вызывается в случае успеха __updateSuccessHandler, если он выдаёт false, то обновление отменяется.
         * * В случае провала вызывается __updateFailHandler.
         *
         * @see \detemiro\modules\database\dbActions::update()
         * 
         * @param  array      $par
         * @param  array|null $cond
         *
         * @return bool
         */
        public function update(array $par, array $cond = null) {
            if($this->__updateBeforeHandler($par, $cond) !== false) {
                $this->db->beginTransaction();

                if($this->db->update($this->table, $par, $cond)) {
                    if($this->__updateSuccessHandler($par, $cond) === false) {
                        $this->db->rollBack();
                    }
                    else {
                        $this->db->commit();

                        return true;
                    }
                }
                else {
                    $this->db->rollBack();
                }
            }

            $this->__updateFailHandler($par, $cond);

            return false;
        }

        /**
         * Обновление элемента
         *
         * @see \detemiro\modules\database\dbActions::update()
         * 
         * @param  mixed $primary
         * @param  array $par
         *
         * @return bool
         */
        public function updateItem($primary, array $par) {
            if($param = $this->getCond($primary)) {
                return $this->update($par, $param);
            }
            else {
                return false;
            }
        }

        /**
         * Вызов общего метода изменения полученных значений
         *
         * @param array $res
         */
        protected function __getHandler(array &$res) {
            foreach($res as $key=>&$item) {
                $this->__torchGET($key, $item);
            }
        }

        /**
         * Обработчик, вызываемый перед отправкой данных
         *
         * @param array $par
         *
         * @return bool
         */
        protected function __addBeforeHandler(array &$par) {
            foreach($par as $key=>&$value) {
                $this->__torchChange($key, $value);

                if($this->__torchCheck($key, $value) == false) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Обработчик, вызываемеый после успешной отправки данных
         *
         * @param array $par
         *
         * @return bool
         */
        protected function __addSuccessHandler(array $par) {}

        /**
         * Обработчик, вызываемый при провальной отправке данных
         *
         * @param array $par
         *
         * @return void
         */
        protected function __addFailHandler(array $par) {}

        /**
         * Обработчик, вызываемый перед обновлением данных
         *
         * @param array      $par
         * @param array|null $cond
         *
         * @return bool
         */
        protected function __updateBeforeHandler(array &$par, array $cond = null) {
            foreach($par as $key=>&$value) {
                $this->__torchChange($key, $value);

                if($this->__torchCheck($key, $value) == false) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Обработчик, вызываемый после успешного обновления данных
         *
         * @param array      $par
         * @param array|null $cond
         *
         * @return bool
         */
        protected function __updateSuccessHandler(array $par, array $cond = null) {}

        /**
         * Обработчик, вызываемый после неудачного обновления данных
         *
         * @param array      $par
         * @param array|null $cond
         *
         * @return void
         */
        protected function __updateFailHandler(array $par, array $cond = null) {}

        /**
         * Вызов метода проверки по ключу.
         * Если метода не существует, то результат - true.
         *
         * @param string $key
         * @param mixed  $value
         *
         * @return bool
         */
        protected function __torchCheck($key, &$value) {
            $key = "__check_$key";

            if(method_exists($this, $key)) {
                return (bool) $this->$key($value);
            }
            else {
                return true;
            }
        }

        /**
         * Вызов метода измененмя значения по ключу.
         *
         * @param string $key
         * @param mixed  &$value
         */
        protected function __torchChange($key, &$value) {
            $key = "__change_$key";

            if(method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        /**
         * Вызов метода измененмя значения по ключу.
         *
         * @param string $key
         * @param mixed  &$value
         */
        protected function __torchGET($key, &$value) {
            $key = "__get_$key";

            if(method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        /**
         * Проверка ID
         *
         * @param mixed $value
         *
         * @return bool
         */
        public function __check_id($value) {
            return (is_numeric($value));
        }

        /**
         * Проверка кода
         *
         * @param mixed $value
         *
         * @return bool
         */
        public function __check_code($value) {
            return \detemiro\validate_code($value);
        }

        /**
         * Нормализация кода
         *
         * @param mixed $value
         */
        public function __change_code(&$value) {
            $value = \detemiro\canone_code($value);
        }
    }
?>