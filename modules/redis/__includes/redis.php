<?php
    namespace detemiro\modules\redis;

    /**
     * Класс для работы с Redis
     *
     * @throws \Exception Если не настроен модуль Redis или не удаётся подсоединиться к серверу.
     */
    class redis extends \detemiro\modules\abstractClassCache\cacheActions {
        protected $config = array(
            'host' => 'localhost',
            'port' => 6379
        );

        public function __construct(array $params = null) {
            parent::__construct($params);

            if(is_string($this->config['host']) == false || $this->config['host'] == '') {
                $this->config['host'] = 'localhost';
            }
            if(is_numeric($this->config['port']) == false) {
                $this->config['port'] = 6379;
            }

            if(class_exists('Redis')) {
                $this->connect = new \Redis();

                if($this->connect->pconnect($this->config['host'], $this->config['port']) == false) {
                    throw new \Exception("Не удаётся соединиться с Redis-сервером {$this->config['host']}:{$this->config['port']}.");
                }
            }
            else {
                throw new \Exception('Неверная конфигурация для Redis.');
            }
        }

        public function set($table, $data, $life = null) {
            if($life === false) {
                $life = 0;
            }
            elseif(is_numeric($life) == false || $life < 60) {
                $life = $this->life;
            }

            return $this->connect->set($this->prefix.$table, $data, $life);
        }

        public function get($table) {
            return $this->connect->get($this->prefix.$table);
        }

        public function replace($table, $data, $life = null) {
            if($this->exists($this->prefix.$table)) {
                if($life === false) {
                    $life = 0;
                }
                elseif(is_numeric($life) == false || $life < 60) {
                    $life = $this->life;
                }

                return $this->connect->set($this->prefix.$table, $data, $life);
            }
            else {
                return false;
            }
        }

        public function delete($table) {
            $this->connect->delete($this->prefix.$table);

            return $this->exists($table);
        }

        public function exists($table) {
            return $this->connect->exists($this->prefix.$table);
        }

        public function inc($table, $value = 1) {
            return $this->connect->incrBy($this->prefix.$table, $value);
        }

        public function dec($table, $value = 1) {
            return $this->connect->decrBy($this->prefix.$table, $value);
        }
    }
?>