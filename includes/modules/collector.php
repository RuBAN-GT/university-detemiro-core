<?php
    namespace detemiro\modules;

    /**
     * Коллектор модулей
     */
    class collector {
        /**
         * Коллектор сообщений
         * 
         * @var array $list
         */
        protected $list = array();

        /**
         * Возвращает поле $list с копиями объектов
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
         * Объект управления статусами в памяти
         *
         * @var memoryStatuses $memory
         */
        protected $memory;

        /**
         * Объект управления статусами
         *
         * @var iStatuses $local
         */
        protected $local;

        /**
         * Статус инициализации объекта из $outer
         *
         * @var bool $localInit
         */
        protected $localInit = false;

        /**
         * Текущий модуль
         *
         * Копия объекта текущего, подключенного модуля. Используется во вспомогательных методах, например, incFile().
         * 
         * @var \detemiro\modules\module $current
         */
        protected $current;
        public function current() {
            if($this->current) {
                return $this->current;
            }
            else {
                return null;
            }
        }

        /**
         * Система зависимостей модулей
         *
         * @var relationsCollector $relations
         */
        protected $relations;

        /**
         * Получение поля $relations
         *
         * @return relationsCollector
         */
        public function relations() {
            return $this->relations;
        }

        /**
         * Коллектор дополнительных подгрузок
         *
         * @var \detemiro\modules\externals
         */
        protected $ext;

        /**
         * Получшение поля $ext
         *
         * @return \detemiro\modules\externals
         */
        public function ext() {
            return $this->ext;
        }

        /**
         * Пересканирование всех модулей на наличие дополнительных директорий
         *
         * @return int
         */
        public function reScanExt() {
            $i = 0;

            foreach($this->list as $module) {
                if($module->status >= 2) {
                    $i += $this->ext->scan("{$module->path}/__externals", $module->code);
                }
            }

            return $i;
        }

        /**
         * Конструктор
         */
        public function __construct() {
            $this->memory    = new memoryStatuses();
            $this->local     = &$this->memory;
            $this->ext       = new externals();
            $this->relations = new relationsCollector();
        }

        /**
         * Добавление модуля в коллектор
         *
         * @see \detemiro\modules\module
         * 
         * @param  string $path Абсолютный путь до директории с модулем
         *
         * @return bool
         */
        public function push($path) {
            try {
                $custom = new module($path);
            }
            catch(\Exception $error) {
                \detemiro::messages()->push(array(
                    'title'  => 'Ошибка добавления модуля',
                    'type'   => 'system',
                    'status' => 'warning',
                    'text'   => $error->getMessage()
                ));

                return false;
            }

            if(\detemiro::space()->code == $custom->space) {
                $this->list[$custom->code] = $custom;

                $this->ext()->addType($custom->code);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Удаление модуля из коллектора
         *
         * Данный метод удаляет модуль из коллектора, если он не был запущен.
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function remove($code) {
            if(isset($this->list[$code]) && $this->list[$code]->run == false) {
                unset($this->list[$code]);

                $this->ext()->removeType($code);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Сканирование директории в поисках модулей
         * 
         * @param  array|string $paths Абсолютные пути до директорий с модулями
         *
         * @return int Число добавленных модулей
         */
        public function scan($paths) {
            $paths = \detemiro\take_good_array($paths, true);

            $i = 0;

            foreach($paths as $root) {
                if(is_string($root)) {
                    $root = realpath($root);

                    if(is_dir($root)) {
                        foreach(new \DirectoryIterator($root) as $sub) {
                            if($sub->isDot()) {
                                continue;
                            }
                            else {
                                if($sub->isDir() && $this->push($sub->getPathname())) {
                                    $i++;
                                }
                            }
                        }
                    }
                }
            }

            return $i;
        }

        /**
         * Подключение объекта для управления статусами модулей и
         * определение их статусов
         *
         * return void
         */
        public function initStatuses() {
            if($this->localInit == false) {
                if($obj = \detemiro::services()->get('modulesStatuses')) {
                    if($obj instanceof iStatuses) {
                        $this->local = &$obj;
                    }
                    else {
                        \detemiro::messages()->push(array(
                            'type'   => 'system',
                            'status' => 'error',
                            'title'  => 'Ошибка статусов модулей',
                            'text'   => "Объект из сервиса 'modulesStatuses' не является наследником 'iStatuses'."
                        ));
                    }
                }
                elseif(\detemiro::config()->memory != true) {
                    try {
                        $tmp = new jsonStatuses();

                        $this->local = &$tmp;
                    }
                    catch(\Exception $error) {
                        \detemiro::messages()->push(array(
                            'type'   => 'system',
                            'status' => 'error',
                            'title'  => 'Ошибка статусов модулей',
                            'text'   => 'Невозможно подключить JSON-статусы.'
                        ));

                        $this->local = &$this->memory;
                    }
                }

                $this->localInit = true;

                /**
                 * Перенос статусов из памяти и установка новых
                 */
                if($this->local != $this->memory) {
                    foreach($this->list as &$module) {
                        $status = $this->local->get($module->code);

                        if($status !== null) {
                            $memory = $this->memory->get($module->code);
                            if($memory !== null && $memory !== $status && $memory > $status) {
                                $this->local->set($module->code, $memory);
                            }
                            else {
                                $module->status = $status;
                            }
                        }
                    }
                }
            }
        }

        /**
         * Подключение всех модулей из коллектора
         * 
         * @return int Число запущенных модулей
         */
        public function build() {
            $i = 0;

            foreach($this->list as &$module) {
                if($module->status >= 2) {
                    $this->current = clone $module;

                    if($module->run()) {
                        $i++;
                    }

                    $this->current = null;
                }
            }

            return $i;
        }

        /**
         * Получение объекта модуля по ссылке
         * 
         * @param  string $code
         *
         * @return \detemiro\modules\module
         */
        protected function &refGet($code) {
            if(isset($this->list[$code])) {
                return $this->list[$code];
            }
            else {
                return null;
            }
        }

        /**
         * Получение копии объекта модуля
         * 
         * @param  string $code
         *
         * @return \detemiro\modules\module
         */
        public function get($code) {
            if(isset($this->list[$code])) {
                return clone $this->list[$code];
            }
            else {
                return null;
            }
        }

        /**
         * Проверка существования модуля
         * 
         * @param  string $code
         *
         * @return bool
         */
        public function exists($code) {
            return (isset($this->list[$code]));
        }

        /**
         * Проветь статус модуля
         * 
         * @param  string $code
         *
         * @return int
         */
        public function status($code) {
            return $this->local->get($code);
        }

        public function isRunned($code) {
            if(isset($this->list[$code]) && $this->list[$code]->run >= 1) {
                return true;
            }

            return false;
        }

        /**
         * Запуск модуля
         *
         * @see \detemiro\modules\module::run()
         *
         * @param  string $code
         *
         * @return bool
         */
        public function run($code) {
            if($module = $this->refGet($code)) {
                $this->current = clone $module;

                $res = $module->run();

                $this->current = null;

                return $res;
            }

            return false;
        }

        /**
         * Установка модуля
         *
         * @see \detemiro\modules\module::install()
         *
         * @param  string $code
         *
         * @return bool
         */
        public function install($code) {
            if($this->refGet($code)->install()) {
                $this->local->set($code, 1);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Активация модуля
         *
         * @see \detemiro\modules\module::activate()
         *
         * @param  string $code
         *
         * @return bool
         */
        public function activate($code) {
            if($this->refGet($code)->activate()) {
                $this->local->set($code, 2);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Деактивация модуля
         *
         * @see \detemiro\modules\module::deactivate()
         *
         * @param  string $code
         *
         * @return bool
         */
        public function deactivate($code) {
            if($this->refGet($code)->deactivate()) {
                $this->local->set($code, 1);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Удаление модуля
         *
         * @see \detemiro\modules\module::uninstall()
         *
         * @param  string $code
         *
         * @return bool
         */
        public function uninstall($code) {
            if($this->refGet($code)->uninstall()) {
                $this->local->remove($code);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Получение модуля по его пути
         * 
         * @param  string      $path
         *
         * @return string|null
         */
        public function getByPath($path) {
            if(is_string($path)) {
                $path = \detemiro\norm_path($path);

                foreach($this->list as $key=>$item) {
                    if(strpos($path, $item->path) === 0) {
                        return clone $item;
                    }
                }
            }

            return null;
        }

        /**
         * Получение абсолютной ссылки до файла модуля
         *
         * Данный метод возвращает строку с абсолютным путём до файла в директории модулей проекта или до файла модуля, если указан $module с магической константой __FILE__.
         * 
         * @param  string      $file   Относительный путь до файла
         * @param  string      $module Содержание __FILE__ или другой путь до директории модуля
         *
         * @return string|null
         */
        public function getPath($file, $module = null) {
            if(is_string($file)) {
                if($module = $this->getByPath($module)) {
                    $module = $module->path;
                }
                else {
                    $module = \detemiro::space()->path . '/det-content/modules';
                }

                if($module) {
                    $path = "$module/" . ltrim($file, '/.');

                    if(file_exists($path)) {
                        return $path;
                    }
                }
            }

            return null;
        }

        /**
         * Подключение файла, если он существует, через include
         *
         * Данный метод подключает файл из директории модулей проекта или файл модуля, если указан $module с использованием магической константой __FILE__.
         * 
         * @param  string     $file   Внутренняя ссылка на файл
         * @param  string     $module Содержание __FILE__ или другой путь до директории модуля
         * @param  array|null $args   Локальные переменные, создаваемые на базе ключей этого массива с префиксом 'det_'
         *
         * @return bool
         */
        public function incFile($file, $module = null, array $args = null) {
            if($path = $this->getPath($file, $module)) {
                if($args) {
                    extract($args, EXTR_PREFIX_ALL, 'det');
                }

                include($path);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Загрузчик класса из модуля
         * 
         * @param  string $name
         *
         * @return bool
         */
        public function classLoader($name) {
            if(strpos($name, 'detemiro\\modules\\') === 0) {
                $name = explode('\\', substr($name, 17));

                if(count($name) > 1) {
                    $code = array_shift($name);

                    if($module = $this->get($code)) {
                        if($module->status > 0) {
                            $try = "{$module->path}/__includes/" . implode('/', $name) . '.php';

                            if(file_exists($try)) {
                                include($try);

                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }
    }
?>