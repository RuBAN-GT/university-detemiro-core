<?php
    namespace detemiro\modules;

    /**
     * Интерфейс для контроля статусов модулей
     */
    interface iStatuses {
        /**
         * Получение статуса модуля по его коду
         *
         * @param  string $code
         *
         * @return int|null
         */
        public function get($code);

        /**
         * Установка статуса модуля
         *
         * @param  string $code
         * @param  int    $status
         *
         * @return bool
         */
        public function set($code, $status);

        /**
         * Удаление статуса модуля
         *
         * @param  string $code
         *
         * @return bool
         */
        public function remove($code);
    }
?>