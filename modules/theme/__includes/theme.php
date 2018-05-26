<?php
    namespace detemiro\modules\theme;

    /**
     * Класс темы
     */
    class theme extends \detemiro\magicControl {
        /**
         * Путь до директории темы
         * 
         * @var string $path
         */
        protected $path;

        /**
         * Код темы
         * 
         * @var string $code
         */
        protected $code;

        /**
         * Информация о теме
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
         * Автор темы
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
         * Версия темы
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
         * Инициализация объекта темы
         *
         * @param string $theme Имя директории с темой ({$проект}/det-content/themes/{$folder})
         *
         * @throws \Exception Если темы не существует.
         * @throws \Exception Если код темы не корректен.
         * @throws \Exception Если тема не прошла собственную проверку.
         */
        public function __construct($theme = 'master'){
            if($theme && is_string($theme)) {
                $folder = $theme;
            }
            else {
                $folder = 'master';
            }

            $this->path = \detemiro::space()->path . "/det-content/themes/$folder";

            if(is_dir($this->path)) {
                /**
                 * Анализ дополнительной информации
                 */
                if($local = \detemiro\read_json("{$this->path}/info.json")) {
                    $this->__propUpdate($local);
                }

                /**
                 * Определение кода темы
                 */
                if($folder == 'master') {
                    $this->code = $folder;
                }
                elseif($theme_name = \detemiro\translite($folder)) {
                    $this->code = \detemiro\canone_code($theme_name);
                }
                else {
                    $this->code = \detemiro\canone_code($folder);
                }
                if(\detemiro\validate_code($this->code) == false) {
                    throw new \Exception('Неверный код темы.');
                }

                /**
                 * Создание отношений
                 */
                if($this->rels) {
                    \detemiro::modules()->relations()->pack($this->rels, 'theme');
                }

                /**
                 * Закрываю поля
                 */
                $this->__ignoreSET[] = 'rels';
                $this->__ignoreSET[] = 'code';

                /**
                 * Проверка
                 */
                if(
                    \detemiro::modules()->relations()->check('theme') &&
                    \detemiro::actions()->makeCheckFileZone("{$this->path}/prepare.php", 'theme.prepare') !== false
                )
                {
                    \detemiro::modules()->ext()->scan("{$this->path}/__externals", 'theme');

                    $this->incFile('main.php');
                }
                else {
                    throw new \Exception('Тема не прошла собственную проверку.');
                }
            }
            else {
                throw new \Exception("Тема {$this->path} не существует.");
            }
        }

        /**
         * Получение абсолютной ссылки до файла темы, если он существует
         * 
         * @param  string      $file Внутренняя ссылка на файл
         *
         * @return string|null
         */
        public function getPath($file) {
            if(is_string($file)) {
                $path = $this->path . '/' . ltrim($file, '/.');

                if(file_exists($path)) {
                    return $path;
                }
            }

            return null;
        }

        /**
         * Подключение файла темы, если он существует, через include
         * 
         * @param  string     $file Внутренняя ссылка на файл
         * @param  array|null $args Локальные переменные, создаваемые на базе ключей этого массива с префиксом 'det_'
         *
         * @return bool
         */
        public function incFile($file, array $args = null) {
            if($path = $this->getPath($file)) {
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
         * Получение абсолютной ссылки до файла модуля в теме или из самой директории модуля
         * 
         * @param  string      $file   Относительный путь до файла
         * @param  string      $module Содержание __FILE__ или другой путь до директории модуля
         *
         * @return string|null
         */
        public function getModulePath($file, $module) {
            if(is_string($file)) {
                $file = ltrim($file, '/.');

                if($module = \detemiro::modules()->getByPath($module)) {
                    if(file_exists("{$this->path}/__modules/{$module->code}/$file")) {
                        return "{$this->path}/__modules/{$module->code}/$file";
                    }
                    elseif(file_exists("{$module->path}/$file")) {
                        return "{$module->path}/$file";
                    }
                }
            }

            return null;
        }

        /**
         * Подключение файла модуля в темы, если он существует, в противном случае из самого модуля
         *
         * @param  string     $file   Внутренняя ссылка на файл
         * @param  string     $module Содержание __FILE__ или другой путь до директории модуля
         * @param  array|null $args   Локальные переменные, создаваемые на базе ключей этого массива с префиксом 'det_'
         *
         * @return bool
         */
        public function incModuleFile($file, $module, array $args = null) {
            if($path = $this->getModulePath($file, $module)) {
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
         * Получение URL до файла темы, если он существует
         *
         * Данный метод, возвращает ссылку, формирующуюся на базе основной ссылки пространства.
         * 
         * @param  string      $file Внутренняя ссылка на файл
         *
         * @return string|null
         */
        public function getFileLink($file) {
            if(\detemiro::router() && is_string($file)) {
                $file = ltrim($file, '/.');

                if(file_exists($this->path . "/$file")) {
                    return \detemiro::router()->url . '/det-content/themes/' . basename($this->path) . "/$file";
                }
            }

            return null;
        }

        /**
         * Получение URL до файла модуля в теме или до директории локального модуля
         *
         * Данный метод, возвращает ссылку, формирующуюся на базе основной ссылки пространства.
         * 
         * @param  string      $file   Внутренняя ссылка на файл
         * @param  string      $module Содержание __FILE__ или другой путь до директории модуля
         *
         * @return string|null
         */
        public function getModuleLink($file, $module) {
            if(\detemiro::router() && is_string($file) && ($module = \detemiro::modules()->getByPath($module))) {
                $file = ltrim($file, '/.');

                if(file_exists("{$this->path}/modules/{$module->code}/$file")) {
                    return \detemiro::router()->url . '/det-content/themes/' . basename($this->path) . "/modules/{$module->code}/$file";
                }
                elseif(file_exists(\detemiro::space()->path . '/det-content/modules/' . basename($module->path) . "/$file")) {
                    return \detemiro::router()->url . '/det-content/modules/' . basename($module->path) . "/$file";
                }
            }

            return null;
        }
    }
?>