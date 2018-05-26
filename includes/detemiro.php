<?php
    /**
     * Класс-коннектор
     * 
     * Сердце ядра, соединяющая все базные компоненты
     *
     * @throws \Exception Если невозможно определить исполняющий файл.
     */
    class detemiro {
        use detemiro\single;

        /**
         * Путь до директории с ядром
         * 
         * @var string $path
         */
        protected static $path = '';
        /**
         * Возвращает поле $path
         * 
         * @return string
         */
        public static function path() {
            return self::$path;
        }

        /**
         * Имя системы
         * 
         * @var string $name
         */
        protected static $name = 'detemiro engine';
        /**
         * Возвращает поле $name
         * 
         * @return string
         */
        public static function name() {
            return self::$name;
        }

        /**
         * Версия системы
         * 
         * @var string $version
         */
        protected static $version = '1.0';
        /**
         * Возвращает поле $version
         * 
         * @return string|int
         */
        public static function version() {
            return self::$version;
        }

        /**
         * Ник системы
         * 
         * @var string $nick
         */
        protected static $nick = 'Basic';
        /**
         * Возврщает поле $nick
         * 
         * @return string
         */
        public static function nick() {
            return self::$nick;
        }

        /**
         * Ссылка на исполняющий файл (тот файл, который подключил load.php из директории ядра)
         * 
         * @var string $file
         */
        protected static $file;
        /**
         * Возвращает поле $file
         * 
         * @return string
         */
        public static function file() {
            return self::$file;
        }

        /**
         * Объект с текущим пространством
         * 
         * @var \detemiro\space $space
         */
        protected static $space;
        /**
         * Возвращает поле $space
         * 
         * @return \detemiro\space
         */
        public static function space() {
            return self::$space;
        }

        /**
         * Семейство проекта
         * 
         * @var array $family
         */
        protected static $family = array();
        /**
         * Возвращает поле $family
         * 
         * @return array
         */
        public static function family() {
            return self::$family;
        }

        /**
         * Коллектор экшенов
         * 
         * @var \detemiro\events\actions $actions
         */
        protected static $actions;
        /**
         * Получение коллектора экшенов
         * 
         * @return \detemiro\events\actions
         */
        public static function actions() {
            return self::$actions;
        }

        /**
         * Объект сообщений
         * 
         * @var \detemiro\messages\message $messages
         */
        protected static $messages;
        /**
         * Возвращает поле $messages
         * 
         * @return \detemiro\messages\collector
         */
        public static function messages() {
            return static::$messages;
        }

        /**
         * Объект модулей
         * 
         * @var \detemiro\modules\collector $modules
         */
        protected static $modules;
        /**
         * Возвращает поле $modules
         * 
         * @return \detemiro\modules\collector
         */
        public static function modules() {
            return self::$modules;
        }

        /**
         * Объект регистра
         * 
         * @var \detemiro\registry $registry
         */
        protected static $registry;
        /**
         * Возвращает поле $registry
         * 
         * @return \detemiro\registry
         */
        public static function registry() {
            return self::$registry;
        }

        /**
         * Сервисы
         *
         * @var \detemiro\serviceContainer
         */
        protected static $services;
        /**
         * Возвращает поле $services
         *
         * @return \detemiro\serviceContainer
         */
        public static function services() {
            return self::$services;
        }

        /**
         * Объект для конфигурации проекта
         * 
         * @var \detemiro\registry $config
         */
        protected static $config;
        /**
         * Возвращает поле $config
         * 
         * @return \detemiro\registry
         */
        public static function config() {
            return self::$config;
        }

        /**
         * Статус запуска
         *
         * Меняется при успешном вызове метода detemiro::run()
         * 
         * @see detemiro::run()
         * 
         * @var bool $run
         */
        protected static $run = false;

        /**
         * Одиночка
         *
         * **Возможные ключи $params**
         *
         * Ключ     | Описание
         * -------- | --------
         * mode     | Код режима для зоны основных экшенов проекта
         * space    | Код/описание необходимого пространства
         * 
         * * *space - Аргумент для конструктора пространства, может содержать код нужного пространства (string) или его задавать (array).*
         * * *Ключи space смотрите в инструкциях по настройке пространства.*
         * 
         * @see \detemiro\single
         * @see \detemiro\space
         * 
         * @param  array|null $params Аргументы для конструктора detemiro
         *
         * @return self
         */
        public static function main(array $params = null) {
            if(self::$instance == null) {
                self::$instance = new self($params);
            }

            return self::$instance;
        }

        /**
         * Конструктор
         *
         * @see detemiro::main()
         *
         * @param array|null $config
         *
         * @throws Exception Если невозможно определить исполняемый файл.
         */
        private function __construct(array $config = null) {
            /**
             * Определение пути до директории с ядром
             */
            self::$path = \detemiro\norm_path(dirname(dirname(__FILE__)));

            /**
             * Определение исполняющего файла
             */
            foreach(debug_backtrace(false) as $piece) {
                if($piece['class'] == 'detemiro' && $piece['function'] == 'main') {
                    self::$file = $piece['file'];

                    break;
                }
            }
            if(self::$file == null) {
                throw new Exception('Невозможно определить исполняющий файл.');
            }

            /**
             * Считывание информации о ядре
             */
            if($local = detemiro\read_json(self::$path . '/system.json')) {
                if(array_key_exists('name', $local) && is_string($local['name'])) {
                    self::$name    = $local['name'];
                }
                if(array_key_exists('version', $local) && is_string($local['version'])) {
                    self::$version = $local['version'];
                }
                if(array_key_exists('nick', $local) && is_string($local['nick'])) {
                    self::$nick    = $local['nick'];
                }
            }

            /**
             * Инициализация сообщений
             */
            self::$messages = new detemiro\messages\collector();

            /**
             * Инициализация и определение пространства, а также создание обработчика автозагрузки класса
             */
            if($config && array_key_exists('space', $config)) {
                $space = $config['space'];

                unset($config['space']);
            }
            else {
                $space = null;
            }

            self::$space = new detemiro\space($space);

            spl_autoload_register(array(self::$space, 'classLoader'));


            /**
             * Инициализация конфигурации
             */
            self::$config = new detemiro\registry();

            $tmp = array(
                'mode'    => 'default',
                'inherit' => false,
                'memory'  => false
            );

            if($local = detemiro\read_json(self::$space->path . '/det-space/config.json')) {
                $tmp = array_replace_recursive($tmp, $local);
            }
            if(self::$space->config) {
                $tmp = array_replace_recursive($tmp, self::$space->config);
            }
            if($config) {
                $tmp = array_replace_recursive($tmp, $config);
            }

            /**
             * Определение родительских проектов
             */
            $family = array();
            if(array_key_exists('inherit', $tmp) && $tmp['inherit']) {
                $path = dirname(self::$space->path);
                while(is_dir("$path/det-space")) {
                    $family[] = $path;

                    $path = dirname($path);
                }

                $family = array_reverse($family);
            }

            $family[] = self::$space->path;

            self::$family = $family;

            $tmp2 = array();
            foreach($family as $member) {
                if($local = detemiro\read_json("$member/det-space/config.json")) {
                    $tmp2 = array_merge($tmp2, $local);
                }
            }
            $tmp = array_replace($tmp2, $tmp);

            /**
             * Финальное сохранение конфигурации
             */
            foreach($tmp as $key=>$item) {
                self::$config->set($key, $item);
            }

            /**
             * Проверка стандартных полей
             */
            if(self::$config->get('mode') !== '' && self::$config->get('mode') !== null && is_string(self::$config->get('mode')) == false) {
                self::$messages->push(array(
                    'title'  => 'Ошибка режима (mode)',
                    'type'   => 'detemiro',
                    'status' => 'error',
                    'text'   => 'Значение должно быть строкой или null.'
                ));
            }

            /**
             * Инициализация главного регистра
             */
            self::$registry = new detemiro\registry();

            /**
             * Инициализация контейнера сервисов
             */
            self::$services = new detemiro\serviceContainer();

            /**
             * Инициализация экшенов
             */
            self::$actions = new detemiro\events\actions();

            /**
             * Инициализация модулей и обработчика для загрузки классов
             *
             * Конфигурация модулей считывается из файла {проект}/det-space/modules.{имя_пространства}.json
             */
            self::$modules = new detemiro\modules\collector();

            spl_autoload_register(array(self::$modules, 'classLoader'));

            /**
             * Установка отношений пространства и конфигурации
             */
            if(self::$config->rels && is_array(self::$config->rels)) {
                self::$modules->relations()->pack(self::$config->rels, 'detemiro');
            }
            if(self::$space->rels && is_array(self::$space->rels)) {
                self::$modules->relations()->pack(self::$space->rels, 'space');
            }

            /**
             * Установка стандартных значений для регистров, если нет ошибок
             */
            if($messages = self::$messages->getType('detemiro', 'error')) {
                foreach($messages as $message) {
                    throw new Exception("({$message->title}): {{$message->text}}");
                }
            }
            else {
                /**
                 * Сканирование модулей по семейству по аналогии с конфигурацией.
                 */
                $path = array(self::$path . '/modules');
                foreach($family as $member) {
                    $path[] = "$member/det-content/modules";
                }
                self::$modules->scan($path);

                self::$modules->ext()->addType('actions');
            }
        }

        /**
         * Запуск системы
         *
         * Данный метод собирается все модули, открывает зоны, храня вывод в буфере.
         *
         * @throws Exception Если возникают ошибки при подключении модулей или при проверке пространства.
         */
        public static function run() {
            self::$modules->initStatuses();

            /**
             * Проверка на инициализацию, запуск и ошибки
             */
            if(
                self::$instance &&
                self::$run == false &&
                self::$messages->sizeStatus('error') == 0 &&
                \detemiro::modules()->relations()->check('detemiro')
            )
            {
                if(\detemiro::modules()->relations()->check('space')) {
                    if(\detemiro::actions()->makeCheckFileZone(self::$space->path . '/det-includes/prepare.php', 'space.prepare') !== false) {
                        self::$run = true;

                        /**
                         * Перевод вывода в буфер
                         *
                         * Буфер будет отдат зоне виджетов 'system.buffer'.
                         */
                        ob_start();

                        /**
                         * Подключение модулей
                         *
                         * Подключение модулей, имеющих статус 'активирован' (2) в конфигурации модулей, а также тех модулей, которые находились в директориях автозагрузки.
                         *
                         * @see detemiro\modules
                         */
                        self::$modules->build();

                        /**
                         * Сканирование дополнительных путей в пространстве
                         *
                         * @see detemiro\modules::exScan()
                         */
                        self::$modules->ext()->scan(self::$space->path . '/det-includes/__externals', 'space');

                        /**
                         * Дополнительное подключение
                         *
                         * Если существует файл {проект}/det-includes/main.php, то он будет подключен.
                         */
                        if(file_exists(self::$space->path . '/det-includes/main.php')) {
                            $a = new \detemiro\fakeClosure();

                            $a->req(self::$space->path . '/det-includes/main.php');
                        }

                        /**
                         * Запуск первой зоны экшенов 'system.start'
                         */
                        self::actions()->makeZone('system.start');

                        /**
                         * Опредление метода работы (основной зоны)
                         */
                        $mode = self::$config->get('mode');
                        if($mode && is_string($mode)) {
                            self::actions()->make("system.mode.$mode");
                        }

                        /**
                         * Дополнительные эшкены из зоны 'system.final'
                         */
                        self::actions()->makeZone('system.final');

                        /**
                         * Вывод
                         *
                         * Выдача вывода из буфера в экшен 'system.buffer', если существует.
                         * Иначе обычное отображение результата через echo.
                         */
                        $show = ob_get_clean();
                        if($action = self::actions()->get('system.buffer')) {
                            $action->make($show);
                        }
                        else {
                            echo $show;
                        }
                    }
                    else {
                        if($messages = self::$messages->getType('space.prepare', 'error')) {
                            foreach($messages as $message) {
                                throw new Exception("({$message->title}): {{$message->text}}");
                            }
                        }
                    }
                }
                else {
                    foreach(self::$modules->relations()->dumpType('space') as $item) {
                        if($item->status === false) {
                            throw new Exception("{$item->method}: {$item->name}");
                        }
                    }
                }
            }
            else {
                foreach(self::$modules->relations()->dumpType('detemiro') as $item) {
                    if($item->status === false) {
                        throw new Exception("{$item->method}: {$item->name}");
                    }
                }
            }
        }

        /**
         * Связь с сервисами
         * 
         * Данный магический метод проверяет $method в ключах сервиса и выдаёт его значение, если оно существует.
         * 
         * @param  string $method Ключ сервиса
         * @param  mixed  $value  Данный аргумент помещается в инициализирующую функцию
         *
         * @return mixed          Значения ключа $method
         */
        public static function __callStatic($method, $value) {
            array_unshift($value, $method);

            if($data = call_user_func_array(array(self::$services, 'get'), $value)) {
                return $data;
            }
            else {
                return null;
            }
        }
    }
?>