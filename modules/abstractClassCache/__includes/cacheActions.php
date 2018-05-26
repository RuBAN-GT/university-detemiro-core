<?php
    namespace detemiro\modules\abstractClassCache;

    /**
     * Абстрактный класс для работы с кешем
     */
    abstract class cacheActions extends \detemiro\magicControl {
        protected $__ignoreGET = array('connect');

        /**
         * Конфигурация кеш-сервера
         * 
         * @var array $config
         */
        protected $config = array();

        /**
         * Стандартное время жизни кеша
         * 
         * @var int $life
         */
        protected $life = 900;

        /**
         * Префикс ключей
         * 
         * @var string $prefix
         */
        protected $prefix = '';

        /**
         * Соединение
         * 
         * @var object
         */
        protected $connect;

        /**
         * Установка значения ключа
         * 
         * @param  string $key
         * @param  mixed  $data
         * @param  int    $life Время жизни кеша
         *
         * @return bool
         */
        abstract public function set($key, $data, $life = null);

        /**
         * Получение значения ключа
         * 
         * @param  string $key
         *
         * @return mixed
         */
        abstract public function get($key);

        /**
         * Обновление значения ключа
         * 
         * @param  string $key
         * @param  mixed  $data
         * @param  int    $life
         *
         * @return bool
         */
        public function replace($key, $data, $life = null) {
            if($this->exists($this->prefix.$key)) {
                return $this->set($this->prefix.$key, $data, $life);
            }
            else {
                return false;
            }
        }

        /**
         * Удаление ключа
         * 
         * @param  string $key
         *
         * @return bool
         */
        abstract public function delete($key);

        /**
         * Увеличение значения ключа на число
         * 
         * @param  string   $key
         * @param  int      $value
         *
         * @return int|null
         */
        public function inc($key, $value = 1) {
            if(is_numeric($value)) {
                $old = $this->get($this->prefix.$key);

                if(is_numeric($old)) {
                    $value += $old;
                }

                if($this->set($this->prefix.$key, $value, $this->life)) {
                    return $value;
                }
            }

            return null;
        }

        /**
         * Уменьшение значения ключа на число
         * 
         * @param  string   $key
         * @param  int      $value
         *
         * @return int|null
         */
        public function dec($key, $value = 1) {
            if(is_numeric($value)) {
                $old = $this->get($this->prefix.$key);

                if(is_numeric($old)) {
                    $value -= $old;
                }

                if($this->set($this->prefix.$key, $value, $this->life)) {
                    return $value;
                }
            }

            return null;
        }

        /**
         * Существование ключа
         *
         * @param  string $key
         *
         * @return bool
         */
        public function exists($key) {
            return ($this->get($key) !== null);
        }

        /**
         * Инициализация
         *
         * Конструктор для классов кеширования.
         *
         * Стандартные ключи $par (могут дополняться в любом наследнике):
         * * life   - стандартное время жизни кеша
         * * prefix - префикс ключа
         *
         * Остальные параметры, переданные через массив, попадут в поле $config.
         * 
         * Если prefix == true, то он примет следующий вид: '{$код_пространства}-main-'
         * 
         * @param array|null $par
         */
        public function __construct(array $par = null) {
            $this->config = array_replace(array(
                'life'   => null,
                'prefix' => null
            ), $this->config);

            if($par) {
                $this->config = array_replace_recursive($this->config, $par);
            }

            if(is_numeric($this->config['life']) && $this->config['life'] >= 60) {
                $this->life = $this->config['life'];
            }

            if(is_string($this->config['prefix'])) {
                $this->prefix = $this->config['prefix'];
            }
            elseif($this->config['prefix'] == true) {
                $this->prefix = \detemiro::space()->code . '-main.';
            }
        }
    }
?>