<?php
    namespace detemiro\modules;

    /**
     * Модуль
     *
     * Объект модуля
     *
     * @throws \Exception Если модуля не существует.
     * @throws \Exception Если код модуля указан неверно.
     */
    class module extends \detemiro\magicControl {
        /**
         * Код модуля
         *
         * Код берётся из info.json, в противном случае на его место встанет латинское название директории модуля.
         * 
         * @var string $code
         */
        protected $code;
        protected function __set_code($value) {
            if(is_string($value)) {
                $value = \detemiro\canone_code($value);

                if(\detemiro\validate_code($value)) {
                    $this->code = $value;
                }
            }
        }

        /**
         * Код пространства
         *
         * Код пространства, в котором будет работать модуль, по-умолчание - текущее.
         * 
         * @var string $space
         */
        protected $space;
        protected function __set_space($value) {
            if(is_string($value)) {
                $this->space = $value;
            }
        }

        /**
         * Дополнительная информация о модуле
         * 
         * @var string $info
         */
        protected $info;
        protected function __set_info($value) {
            if(is_string($value)) {
                $this->info = $value;
            }
        }

        /**
         * Автор модуля
         * 
         * @var string $author
         */
        protected $author;
        protected function __set_author($value) {
            if(is_string($value)) {
                $this->author = $value;
            }
        }

        /**
         * Версия модуля
         * 
         * @var string $version
         */
        protected $version;
        protected function __set_version($value) {
            if(is_string($value) || is_numeric($value)) {
                $this->version = $value;
            }
        }

        /**
         * Абсолютный путь до директории с модулем
         * 
         * @var string $path
         */
        protected $path;

        /**
         * Зависимости
         *
         * @var array $rels
         */
        protected $rels = array();
        protected function __set_rels($value) {
            if(is_array($value)) {
                $this->rels = $value;
            }
        }

        /**
         * Статус модуля
         *
         * * 0  - модуль существует
         * * 1  - модуль установлен
         * * 2  - модуль должен быть активирован
         * 
         * @var int $status
         */
        protected $status = 0;
        protected function __set_status($value) {
            if(is_numeric($value)) {
                $this->status = $value;
            }
        }

        /**
         * Статус запуска модуля (подключен ли он)
         * * 1  - запущен
         * * 0  - отключен
         * * -1 - заблокирован из-за ошибоки в подготовке
         * 
         * @var int $run
         */
        protected $run = 0;

        /**
         * Конструктор модуля
         *
         * @param string $path   Абсолютный путь до директории с main.php
         *
         * @throws \Exception Если невозможно определить код модуля.
         */
        public function __construct($path) {
            $path = \detemiro\norm_path(realpath($path));

            /**
             * Считывание информации о модуле
             */
            if($local = \detemiro\read_json("$path/info.json")) {
                $this->__propUpdate($local);
            }

            $this->path = $path;

            /**
             * Попытка определить код модуля
             */
            if($this->code == null) {
                $path = basename($this->path);

                if($module_name = \detemiro\translite($path)) {
                    $this->code = \detemiro\canone_code($module_name);
                }
                else {
                    $this->code = \detemiro\canone_code($path);
                }
            }
            if(\detemiro\validate_code($this->code) == false) {
                throw new \Exception("Неверный код модуля [{$this->path}]");
            }

            /**
             * Определение пространства
             */
            $this->space = \detemiro::space()->getCode($this->space);

            /**
             * Создание отношений
             */
            if($this->rels) {
                \detemiro::modules()->relations()->pack($this->rels, "module.{$this->code}");
            }

            /**
             * Закрываю поля
             */
            $this->__ignoreSET[] = 'code';
            $this->__ignoreSET[] = 'space';
            $this->__ignoreSET[] = 'rels';
        }

        /**
         * Подключение модуля
         *
         * Данный метод подключает модуль, вызывая файл prepare.php и зону экшенов module.prepare.{$код_модуля}.
         *
         * @return bool
         */
        public function run() {
            if($this->run == false) {
                if($this->status >= 2) {
                    if(\detemiro::modules()->relations()->check("module.{$this->code}") == false) {
                        return false;
                    }
                }
                elseif($this->activate() == false) {
                    return false;
                }

                if(\detemiro::actions()->makeCheckFileZone("{$this->path}/prepare.php", "module.prepare.{$this->code}") !== false) {
                    $this->run = 1;

                    \detemiro::modules()->ext()->scan("{$this->path}/__externals", $this->code);

                    if(file_exists("{$this->path}/main.php")) {
                        include("{$this->path}/main.php");
                    }

                    return true;
                }
                else {
                    $this->run = -1;
                }
            }
            elseif($this->run >= 1) {
                return true;
            }

            return false;
        }

        /**
         * Установка модуля
         *
         * При установке модуля подключается файл install.php из директории модуля, а также запускается зона module.install.{$код_модуля}.
         * 
         * @return bool
         */
        public function install() {
            if(
                $this->status < 1 &&
                \detemiro::modules()->relations()->check("module.{$this->code}") &&
                \detemiro::actions()->makeCheckFileZone("{$this->path}/install.php", "module.install.{$this->code}") !== false
            )
            {
                $this->status = 1;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Активация модуля
         *
         * При активации модуля подключается файл install.php из директории модуля, а также запускается зона
         * module.activate.{$код_модуля}.
         * 
         * @return bool
         */
        public function activate() {
            if($this->status == 1) {
                if(\detemiro::modules()->relations()->check("module.{$this->code}") == false) {
                    return false;
                }
            }
            elseif($this->status < 1 && $this->install() == false) {
                return false;
            }

            if(\detemiro::actions()->makeCheckFileZone("{$this->path}/activate.php", "module.activate.{$this->code}") !== false) {
                $this->status = 2;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Удаление модуля
         *
         * При активации модуля подключается файл uninstall.php из директории модуля, а также запускается зона
         * module.uninstall.{$код_модуля}.
         * 
         * @return bool
         */
        public function uninstall() {
            if($this->status == 1) {
                if(\detemiro::modules()->relations()->check("module.{$this->code}") == false) {
                    return false;
                }
            }
            elseif($this->status > 1 && $this->deactivate() == false) {
                return false;
            }

            if(\detemiro::actions()->makeCheckFileZone("{$this->path}/uninstall.php", "module.uninstall-{$this->code}") !== false) {
                $this->status = 2;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Деактивация модуля
         *
         * При активации модуля подключается файл uninstall.php из директории модуля, а также запускается зона
         * module.deactivate.{$код_модуля}.
         * 
         * @return bool
         */
        public function deactivate() {
            if(
                $this->status >= 2 &&
                \detemiro::modules()->relations()->check("module.{$this->code}") &&
                \detemiro::actions()->makeCheckFileZone("{$this->path}/deactivate.php", "module.deactivate.{$this->code}") !== false
            )
            {
                $this->status = 1;

                return true;
            }
            else {
                return false;
            }
        }
    }
?>