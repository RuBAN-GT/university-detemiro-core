<?php
    namespace detemiro;

    /**
     * Обходной путь подключение файлов с анонимные функциями для замыкания на fakeClosure
     */
    class fakeClosure {
        public function req($path) {
            require_once($path);
        }
    }
?>