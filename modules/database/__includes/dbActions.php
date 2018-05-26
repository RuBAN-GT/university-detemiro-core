<?php
    namespace detemiro\modules\database;

    /**
     * Класс для работы с БД через PDO
     *
     * @throws \Exception Если неверная конфигурация.
     * @throws \Exception Если не удаётся подсоединиться к БД.
     */
    class dbActions extends \detemiro\magicControl {
        protected $__ignoreGET = array('connect');

        /**
         * PDO-строка конфигурации
         * 
         * @var string $config
         */
        protected $config;

        /**
         * Используемый драйвер
         * 
         * @var string $driver
         */
        protected $driver = 'pdo';

        /**
         * Префикс таблиц
         * 
         * @var string $prefix
         */
        protected $prefix = '';

        /**
         * Соединение
         * 
         * @var \PDO $connect
         */
        protected $connect;

        /**
         * Инициализация объекта БД
         *
         * @param array|string $params
         *
         * @throws \Exception Если задан некорректный аргумент для конструктора
         */
        public function __construct($params) {
            if(is_string($params)) {
                $this->config = $params;

                $default = array(
                    'user' => '',
                    'pass' => ''
                );
            }
            elseif(is_array($params) && array_key_exists('config', $params) && is_string($params['config'])) {
                $default = array_replace(array(
                    'config' => '',
                    'prefix' => '',
                    'user'   => '',
                    'pass'   => ''
                ), $params);

                $this->prefix  = (is_string($default['prefix'])) ? $default['prefix']  : '';
                $this->config  = (is_string($default['config'])) ? $default['config']  : '';
            }
            else {
                throw new \Exception('Неверна конфигурация для соединения PDO.');
            }

            if($this->config) {
                try {
                    if(is_string($default['user']) && is_string($default['pass']) && $default['user'] && $default['pass']) {
                        $this->connect = new \PDO(
                            $this->config,
                            $default['user'],
                            $default['pass']
                        );
                    }
                    else {
                        $this->connect = new \PDO($this->config);
                    }

                    $this->connect->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    if($driver = explode(':', $this->config)) {
                        $this->driver = $driver[0];
                    }
                }
                catch(\PDOException $error) {
                    throw $error;
                }
            }
        }

        /**
         * Выполнение SQL-запроса
         * 
         * @param  string $query
         * @param  bool   $errors Выдача ошибок
         * @param  array  $args   Экранируемые аргументы (в запросе выделяются знаком '?')
         *
         * @return mixed
         */
        public function action($query, $errors = false, array $args = array()) {
            try {
                $sth = $this->connect->prepare($query);

                $sth->execute($args);

                return $sth;
            }
            catch(\PDOException $error) {
                if($errors) {
                    throw $error;
                }
                else {
                    return null;
                }
            }
        }

        /**
         * Выполнение обычного SQL-запроса
         * 
         * @param  string $query
         * @param  bool   $errors
         *
         * @return mixed
         */
        public function query($query, $errors = false) {
            try {
                return $this->connect->query($query);
            }
            catch(\PDOException $error) {
                if($errors) {
                    throw $error;
                }
                else {
                    return null;
                }
            }
        }

        /**
         * Выборка по SQL-запросу
         * 
         * @param  string     $query
         * @param  bool|int   $column Выдаваемый столбец
         * @param  bool|int   $row    Выдаваемая строка
         * @param  bool       $errors Выдача ошибок
         * @param  array      $args   Экранируемые аргументы
         *
         * @return array|null
         */
        public function actionData($query, $column = null, $row = null, $errors = false, array $args = array()) {
            $res = null;

            if($column === true) {
                $column = 0;
            }
            if($row === true) {
                $row = 0;
            }

            /**
             * Формирование данных
             */
            if($res = $this->action($query, $errors, $args)) {
                if(is_numeric($column)) {
                    try {
                        $res = $res->fetchAll(\PDO::FETCH_COLUMN, $column);
                    }
                    catch(\PDOException $error) {
                        if($errors) {
                            throw $error;
                        }
                        else {
                            return null;
                        }
                    }
                }
                else {
                    $res = $res->fetchAll(\PDO::FETCH_ASSOC);
                }

                if(is_array($res) && is_numeric($row)) {
                    if($row < count($res)) {
                        $res = $res[$row];
                    }
                    else {
                        $res = null;
                    }
                }
            }

            return $res;
        }

        /**
         * Умная выборка
         *
         * Данный метод автоматически составляет SQL-запрос выборки по следующему массиву-шаблону:
         *
         * Ключ    | Описание            | Тип          | По-умолчанию
         * ------- | ------------------- | ------------ | ------------
         * table   | Имя таблицы         | string       | ''
         * cols    | Столбцы             | array|string | *
         * oneCol  | Номер столбца       | int|bool     | null
         * oneRow  | Номер строки        | int|bool     | null
         * cond    | Условие             | array|string | null
         * join    | Присоединение       | array|string | ''
         * on      | Условие для JOIN    | array|string | null
         * order   | Сортировка          | array        | null
         * groupBy | Группировка         | array|string | ''
         * having  | Условие группировки | array        | null
         * limit   | Максимальный размер | int          | null
         * offset  | Отступ              | int          | 0
         * error   | Выдача ошибок       | bool         | false
         * result  | Выдача выборки      | bool         | true
         * 
         * @see self::cond()
         * 
         * @param  array      $par
         *
         * @return array|null
         */
        public function select(array $par) {
            $custom = array_replace_recursive(array(
                'table'   => '',
                'cols'    => '*',
                'oneCol'  => null,
                'oneRow'  => null,
                'cond'    => '',
                'join'    => '',
                'on'      => null,
                'order'   => null,
                'groupBy' => '',
                'having'  => null,
                'limit'   => null,
                'offset'  => null,
                'error'   => false,
                'result'  => true
            ), $par);

            if(is_array($custom['cols'])) {
                $custom['cols'] = implode(', ', array_map(function($col) {
                    return $col;
                }, $custom['cols']));
            }

            $args = array();

            $query = "SELECT {$custom['cols']} FROM {$this->prefix}{$custom['table']}";

            /**
             * Генерация присоединения
             */
            if($custom['join']) {
                $custom['join'] = \detemiro\take_good_array($custom['join']);

                if(\detemiro\is_assoc_array($custom['on'])) {
                    $custom['on'] = array($custom['on']);
                }
                else {
                    $custom['on'] = \detemiro\take_good_array($custom['on']);
                }

                foreach($custom['join'] as $i=>$join) {
                    $query .= " $join";

                    if(isset($custom['on'][$i])) {
                        if($custom['on'][$i] && ($custom['on'][$i] = self::cond($custom['on'][$i])) && $custom['on'][$i][0]) {
                            $query .= " ON {$custom['on'][$i][0]}";
                            $args = array_merge($args, $custom['on'][$i][1]);
                        }
                    }
                }
            }

            /**
             * Генерация условия
             */
            if($custom['cond'] && ($custom['cond'] = self::cond($custom['cond'])) && $custom['cond'][0]) {
                $query .= " WHERE {$custom['cond'][0]}";
                $args   = array_merge($args, $custom['cond'][1]);
            }
            /**
             * Генерация группировки
             */
            if($custom['groupBy']) {
                if(is_array($custom['groupBy'])) {
                    $custom['groupBy'] = implode(',', $custom['groupBy']);
                }

                $query .= " GROUP BY {$custom['groupBy']}";

                if($custom['having'] && ($custom['having'] = self::cond($custom['having'])) && $custom['having'][0]) {
                    $query .= " HAVING {$custom['having'][0]}";
                    $args   = array_merge($args, $custom['having'][1]);
                }
            }
            /**
             * Обработка сортировки
             */
            if($custom['order']) {
                $custom['order'] = \detemiro\take_good_array($custom['order'], true);

                $query .= " ORDER BY ";
                array_walk($custom['order'], function(&$type, $col) {
                    if(in_array($type, array('ASC', 'asc', 'DESC', 'desc')) == false) {
                        $type = 'ASC';
                    }

                    $type   = "$col $type";
                });

                $query .= implode(',', $custom['order']);
            }
            /**
             * Добавление лимита и отступа
             */
            if(is_numeric($custom['limit'])) {
                $query .= " LIMIT {$custom['limit']}";
            }
            if(is_numeric($custom['offset'])) {
                $query .= " OFFSET {$custom['offset']}";
            }

            /**
             * Обработка строки SELECT
             */
            if($custom['result']) {
                return $this->actionData($query, $custom['oneCol'], $custom['oneRow'], $custom['error'], $args);
            }
            else {
                return array($query, $args);
            }
        }

        /**
         * Вставка данных
         *
         * Данный метод позволяет вставлять данные в таблицу, используя значениы $par и его ключи как столбцы.
         * 
         * @param  string $table Таблица
         * @param  array  $par   Значения
         * @param  bool   $error Выдача ошибок
         *
         * @return bool
         */
        public function insert($table, array $par, $error = false) {
            if($par) {
                $L = count($par);

                $params = implode(', ', array_map(function($key) {
                    return $key;
                }, array_keys($par)));
                $values = implode(', ', array_pad(array(), $L, '?'));

                $res = $this->action(
                    "INSERT INTO {$this->prefix}$table($params) VALUES($values)",
                    $error,
                    array_values($par)
                );

                return ($res != null);
            }

            return false;
        }

        /**
         * Получение id последней вставки
         *
         * @param  string $sec Последовательность
         *
         * @return string
         */
        public function lastInsertId($sec = null) {
            return $this->connect->lastInsertId($sec);
        }

        /**
         * Обновление данных
         * 
         * @param  string     $table Таблица
         * @param  array      $par   Значения
         * @param  array|null $cond  Условие
         * @param  bool       $error Выдача ошибок
         *
         * @return bool
         */
        public function update($table, array $par, array $cond = null, $error = false) {
            if($par) {
                $args = array_values($par);

                if($cond && ($cond = self::cond($cond))) {
                    $cond[0] = ' WHERE ' . $cond[0];
                    $args = array_merge($args, $cond[1]);
                }

                $values = implode(', ', array_map(function($key) {
                    return "$key=?";
                }, array_keys($par)));

                $res = $this->action("UPDATE {$this->prefix}$table SET $values{$cond[0]}", $error, $args);

                return ($res != null);
            }

            return false;
        }

        /**
         * Удаление данных
         * 
         * @param  string     $table Таблица
         * @param  array|null $cond  Условие
         * @param  bool       $error Выдача ошибок
         *
         * @return bool
         */
        public function delete($table, array $cond = null, $error = false) {
            if($cond && ($cond = self::cond($cond))) {
                $cond[0] = ' WHERE ' . $cond[0];

                $res = $this->action("DELETE FROM {$this->prefix}$table{$cond[0]}", $error, $cond[1]);

                return ($res != null);
            }
            else {
                return false;
            }
        }

        /**
         * Количество строк в таблице по условию
         * 
         * @param  string     $table Таблица
         * @param  array|null $cond  Условие
         * @param  bool       $error Выдача ошибок
         *
         * @return bool
         */
        public function count($table, array $cond = null, $error = false) {
            $args = array();
            if($cond && ($cond = self::cond($cond))) {
                $args = $cond[1];
                $cond = ' WHERE ' . $cond[0];
            }
            else {
                $cond = '';
            }

            if($res = $this->action("SELECT COUNT(*) FROM {$this->prefix}$table$cond", $error, $args)) {
                return $res->fetchColumn();
            }
            else {
                return 0;
            }
        }

        /**
         * Массив столбцов таблицы
         * 
         * @param  string $table Таблица
         *
         * @return array
         */
        public function struct($table) {
            $struct = array();

            if($res = $this->action("SELECT * FROM {$this->prefix}$table LIMIT 0")) {
                for($i = 0; $i < $res->columnCount(); $i++) {
                    $col = $res->getColumnMeta($i);

                    $struct[] = $col['name'];
                }
            }

            return $struct;
        }

        /**
         * Проверка существования таблицы
         * 
         * @param  string $table Таблица
         * @param  bool   $error Выдача ошибок
         *
         * @return bool
         */
        public function tableExists($table, $error = false) {
            $res = $this->query("SELECT 1 FROM {$this->prefix}$table LIMIT 1", $error);

            return ($res != null);
        }

        /**
         * Удаление таблицы
         * 
         * @param  string $table Таблица
         * @param  bool   $error Выдача ошибок
         *
         * @return bool
         */
        public function deleteTable($table, $error = false) {
            $res = $this->query("DROP TABLE {$this->prefix}$table", $error);

            return ($res != null);
        }

        /**
         * Условие
         *
         * Данный метод формирует простые или сложные SQL-условия WHERE.
         *
         * **Шаблон элемента-условия:**
         *
         * Ключ       | Описание                                  | Тип    | По-умолчанию
         * ---------- | ----------------------------------------- | ------ | ------------
         * param      | Аргумент                                  | string | ''
         * rel        | Отношение                                 | string | =
         * value      | Значение                                  | string | ''
         * preValue   | Приставка для значения, например, функция | string | ''
         * postValue  | Суффикс для значения                      | string | ''
         * escape     | Экранирование                             | bool   | true
         * log        | Разделитель                               | string | AND
         * prefix     | Префикс                                   | string | ''
         * suffix     | Суфикс                                    | string | ''
         * body       | Выделение значения                        | string | ''
         * manual     | Ручный запрос                             | string | ''
         * manualArgs | Аргументы ручного запроса                 | array  | array()
         *
         * Данные элементы можно группировать разными способами.
         *
         * **Примеры:**
         * ````php
         * var_dump(self::cond(array(
         *     'param' => 'column',
         *     'value' => 45
         * )));
         *
         * var_dump(array(
         *     array(
         *         array(
         *             'param' => 'ID',
         *             'value' => 2
         *         ),
         *         array(
         *             'param' => 'name',
         *             'value' => 'test'
         *         )
         *     ),
         *     array(
         *         'param' => 'ID',
         *         'value' => 1,
         *         'rel'   => 'OR'
         *     )
         * ));
         * ````
         *
         * **Результат**
         * ````php
         * ['column = ?', [45]]
         *
         * ['(ID = ? AND name = ?) OR ID = 1', [2, 'test', 1]]
         * ````
         *
         * @param  array|string $par Шаблон
         *
         * @return array array(0 => запрос, 1 => аргументы)
         */
        public static function cond($par) {
            if(is_string($par)) {
                $par = array(array('manual' => $par));
            }
            elseif(\detemiro\is_assoc_array($par)) {
                $par = array($par);
            }

            $cond = array('', array());

            if($par && is_array($par)) {
                $custom = array(
                    'param'      => '',
                    'rel'        => '=',
                    'value'      => '',
                    'preValue'   => '',
                    'postValue'  => '',
                    'escape'     => true,
                    'log'        => 'AND',
                    'prefix'     => '',
                    'suffix'     => '',
                    'body'       => '',
                    'manual'     => '',
                    'manualArgs' => array()
                );

                foreach($par as $sub_cond) {
                    if(is_array($sub_cond)) {
                        if(isset($sub_cond[0]) && is_array($sub_cond[0])) {
                            if($cond[0]) {
                                $cond[0] .= (array_key_exists('log', $sub_cond[0])) ? " {$sub_cond[0]['log']}" : " AND";
                                $cond[0] .= " ";
                            }

                            if($sub = self::cond($sub_cond)) {
                                $cond[1]  = array_merge($cond[1], $sub[1]);
                                $cond[0] .= "({$sub[0]})";
                            }
                        }
                        elseif(array_key_exists('param', $sub_cond) || array_key_exists('manual', $sub_cond)) {
                            $tmp = array_replace_recursive($custom, $sub_cond);

                            if($cond[0]) {
                                $cond[0] .= " {$tmp['log']} ";
                            }

                            if($tmp['prefix']) {
                                $cond[0] .= ' ' . $tmp['prefix'];
                            }

                            if(array_key_exists('manual', $sub_cond)) {
                                $cond[0] .= $tmp['manual'];

                                if($tmp['manualArgs']) {
                                    $cond[1] = array_merge($cond[1], $tmp['manualArgs']);
                                }
                            }
                            elseif(array_key_exists('param', $sub_cond)) {
                                $cond[0] .= "{$tmp['param']} {$tmp['rel']} ";

                                if($tmp['preValue']) {
                                    $cond[0] .= $tmp['preValue'];
                                }
                                if($tmp['escape']) {
                                    $cond[1][] = $tmp['value'];
                                    $cond[0] .= "?";
                                }
                                else {
                                    $cond[0] .= "{$tmp['body']}{$tmp['value']}{$tmp['body']}";
                                }
                                if($tmp['postValue']) {
                                    $cond[0] .= $tmp['postValue'];
                                }
                            }

                            if($tmp['suffix']) {
                                $cond[0] .= $tmp['suffix'];
                            }
                        }
                    }
                }
            }

            return $cond;
        }

        /**
         * Инициализация транзакции (используйте с осторожностью)
         *
         * @see http://php.net/manual/ru/pdo.begintransaction.php
         *
         * @return bool
         */
        public function beginTransaction() {
            return $this->connect->beginTransaction();
        }

        /**
         * Фиксация транзакции
         *
         * @see http://php.net/manual/ru/pdo.commit.php
         *
         * @return bool
         */
        public function commit() {
            if($this->connect->inTransaction()) {
                return $this->connect->commit();
            }
            else {
                return false;
            }
        }

        /**
         * Откат транзакции
         *
         * @return bool
         */
        public function rollBack() {
            if($this->connect->inTransaction()) {
                return $this->connect->rollBack();
            }
            else {
                return false;
            }
        }
    }
?>