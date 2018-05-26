<?php
    namespace detemiro\modules;

    /**
     * Контроль статусов модулей через JSON-хранилище
     */
    class jsonStatuses implements iStatuses {
        /**
         * Таблица JSON-файла
         *
         * @var array $content
         */
        protected $content = array();

        /**
         * Абсолютный путь до файла со статусами
         *
         * @var string $file
         */
        protected $file = '';

        /**
         * Конструктор
         *
         * @throws \Exception Если файл не может быть перезаписан.
         * @throws \Exception Если невозможно перезаписать или создать директорию с файлом.
         */
        public function __construct() {
            $this->file = \detemiro::space()->path . '/det-space/modules.' . \detemiro::space()->code . '.json';

            if($this->file && file_exists($this->file)) {
                if(is_writable($this->file) == false) {
                    throw new \Exception("Файл ({$this->file}) должен иметь возможность быть перезаписанным.");
                }
                if($try = \detemiro\read_json($this->file)) {
                    foreach($try as $key=>$item) {
                        $this->content[$key] = (int) $item;
                    }
                }
            }
            elseif(is_dir(dirname($this->file))) {
                if(is_writable(dirname($this->file)) == false) {
                    throw new \Exception('Директория ' . dirname($this->file) . ' должна иметь права на запись.');
                }
            }
            elseif(mkdir(dirname($this->file), 0770) == false) {
                throw new \Exception('Невозможно создать директорию ' . dirname($this->file) . '.');
            }
        }

        public function get($code) {
            if(array_key_exists($code, $this->content)) {
                return (int) $this->content[$code];
            }
            else {
                return null;
            }
        }

        public function set($code, $status) {
            $this->content[$code] = (int) $status;

            return file_put_contents($this->file, \detemiro\json_val_encode($this->content));
        }

        public function remove($code) {
            if(array_key_exists($code, $this->content)) {
                unset($this->content[$code]);

                return file_put_contents($this->file, \detemiro\json_val_encode($this->content));
            }
            else {
                return false;
            }
        }
    }
?>