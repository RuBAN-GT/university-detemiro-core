<?php
    namespace detemiro\modules\xcache;

    /**
     * Класс для работы с XCache
     *
     * @throws \Exception Если не настроен модуль Xcache.
     */
    class xcache extends \detemiro\modules\abstractClassCache\cacheActions {
        public function __construct(array $params = null) {
            parent::__construct($params);

            if(function_exists('xcache_get')) {
                $this->connect = &$this;
            }
            else {
                throw new \Exception('Неверная конфигурация для XCache.');
            }
        }

        public function set($table, $data, $life = null) {
            if(is_numeric($life) == false || $life < 60) $life = $this->life;

            return \xcache_set($this->prefix.$table, $data, $life);
        }

        public function get($table) {
            return \xcache_get($this->prefix.$table);
        }

        public function replace($table, $data, $life = null) {
            if(is_numeric($life) == false || $life < 60) $life = $this->life;

            return \xcache_set($this->prefix.$table, $data, $life);
        }

        public function delete($table) {
            return \xcache_unset($this->prefix.$table);
        }

        public function exists($table) {
            return (\xcache_isset($this->prefix.$table));
        }

        public function inc($table, $value = 1) {
            return \xcache_inc($this->prefix.$table, $value);
        }

        public function dec($table, $value = 1) {
            return \xcache_dec($this->prefix.$table, $value);
        }
    }
?>