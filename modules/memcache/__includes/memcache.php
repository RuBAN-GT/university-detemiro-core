<?php
    namespace detemiro\modules\memcache;

    /**
     * Класс для работы с Memcache
     *
     * @throws \Exception Если не настроен модуль Memcache.
     * @throws \Exception Если не удаётся подсоединиться к Memcache-серверу.
     */
    class memcache extends \detemiro\modules\abstractClassCache\cacheActions {
        protected $config = array(
            'host' => 'localhost',
            'port' => 11211
        );

        public function __construct(array $params = null) {
            parent::__construct($params);

            if(is_string($this->config['host']) == false || $this->config['host'] == '') {
                $this->config['host'] = 'localhost';
            }
            if(is_numeric($this->config['port']) == false) {
                $this->config['port'] = 11211;
            }

            if(class_exists('Memcache')) {
                $this->connect = new \Memcache;

                if($this->connect->pconnect($this->config['host'], $this->config['port']) == false) {
                    throw new \Exception("Не удаётся соединиться с Memcache-сервером {$this->config['host']}:{$this->config['port']}.");
                }
            }
            else {
                throw new Exception("Неверная конфигурация Memcache.");
            }
        }

        public function set($table, $data, $life = null) {
            if(is_numeric($life) == false || $life < 60) $life = $this->life;

            return $this->connect->set($this->prefix.$table, $data, MEMCACHE_COMPRESSED, $life);
        }

        public function get($table) {
            return $this->connect->get($this->prefix.$table);
        }

        public function replace($table, $data, $life = null) {
            if(is_numeric($life) == false || $life < 60) $life = $this->life;

            return $this->connect->replace($this->prefix.$table, $data, MEMCACHE_COMPRESSED, $life);
        }

        public function delete($table) {
            return $this->connect->delete($this->prefix.$table);
        }

        public function flush() {
            return ($this->connect->flush());
        }

        public function exists($table) {
            $this->connect->get($this->prefix . $table);

            return (\Memcached::RES_NOTFOUND !== $this->connect->getResultCode());
        }

        public function inc($table, $value = 1) {
            return $this->connect->increment($this->prefix.$table, $value);
        }

        public function dec($table, $value = 1) {
            return $this->connect->decrement($this->prefix.$table, $value);
        }
    }
?>