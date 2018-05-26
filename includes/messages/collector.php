<?php
    namespace detemiro\messages;

    /**
     * Сообщения
     */
    class collector {
        /**
         * Коллектор сообщений
         * 
         * @var array $list
         */
        protected $list = array();

        /**
         * Возвращает поле $list
         * 
         * @return array
         */
        public function all() {
            return $this->list;
        }

        /**
         * Число сообщений по типам
         * 
         * @var array $countType
         */
        protected $countType = array();

        /**
         * Число сообщений по статусам
         * 
         * @var array $countStatus
         */
        protected $countStatus = array();

        public function __construct() {
            /**
             * Проверка сообщений, сохранённых в сессии.
             */
            if(\detemiro\session_prot_start() && isset($_SESSION['detemiro.messages']) && is_array($_SESSION['detemiro.messages'])) {
                $_SESSION['detemiro.messages'] = array_values($_SESSION['detemiro.messages']);

                $L = count($_SESSION['detemiro.messages']);

                for($i=0; $i<$L; $i++) {
                    if(is_object($_SESSION['detemiro.messages'][$i])) {
                        $this->push($_SESSION['detemiro.messages'][$i]);

                        if($_SESSION['detemiro.messages'][$i]->save == 1) {
                            unset($_SESSION['detemiro.messages'][$i]);
                        }
                        else {
                            $_SESSION['detemiro.messages'][$i]->save--;
                        }
                    }
                }
            }
        }

        /**
         * Определение кода статуса значения
         * 
         * @param  int|string $value
         *
         * @return int|null
         */
        public static function getStatusCode($value) {
            if(is_numeric($value)) {
                return $value;
            }
            else {
                switch($value) {
                    case 'error':
                    case 'alert':
                        return 1;
                    case 'warning':
                    case 'danger' :
                        return 2;
                    case 'success':
                    case 'ok'     :
                        return 3;
                    case 'info'  :
                    case 'notice':
                        return 4;
                    default:
                        return null;
                }
            }
        }

        /**
         * Получениее сообщений по типам и статусам
         *
         * Данный метод возвращает сообщения, указанных типов и статусов.
         * Если тип и статус не указан, то результатом будет служить весь коллектор.
         * 
         * Результат будет представлял себя ассоциативным массив вида:
         * > Ключ - тип, значение ключа - массив сообщений данного типа.
         * 
         * @param  array|string|null $types
         * @param  array|string|null $statuses
         *
         * @return array
         */
        public function get($types = null, $statuses = null) {
            $res = array();

            if($types) {
                $types = \detemiro\take_good_array($types, true);

                foreach($types as $type) {
                    if(is_string($type) && isset($this->list[$type])) {
                        $res[$type] = $this->list[$type];
                    }
                }
            }
            else {
                $res = $this->list;
            }

            if($statuses) {
                $statuses = \detemiro\take_good_array($statuses, true);

                foreach($statuses as &$status) {
                    $status = self::getStatusCode($status);
                }

                $tmp = array();

                foreach($res as $type=>$block) {
                    foreach($block as $item) {
                        if(in_array($item->status, $statuses)) {
                            $tmp[$type][] = $item;
                        }
                    }
                }

                $res = $tmp;
            }

            return $res;
        }

        /**
         * Получениее сообщений одного типа и статусам
         *
         * @param  string|null       $type
         * @param  array|string|null $statuses
         *
         * @return array
         */
        public function getType($type, $statuses = null) {
            $res = array();

            if(isset($this->list[$type])) {
                if($statuses) {
                    $statuses = \detemiro\take_good_array($statuses, true);
                    foreach($statuses as &$status) {
                        $status = self::getStatusCode($status);
                    }

                    foreach($this->list[$type] as $item) {
                        if(in_array($item->status, $statuses)) {
                            $res[] = $item;
                        }
                    }
                }
                else {
                    $res = $this->list[$type];
                }
            }

            return $res;
        }

        /**
         * Количество сообщений указанного типа
         * 
         * @param  string $type
         *
         * @return int
         */
        public function sizeType($type) {
            if(isset($this->countType[$type])) {
                return $this->countType[$type];
            }
            else {
                return 0;
            }
        }

        /**
         * Количество сообщений указанного статуса
         * 
         * @param  string $status
         *
         * @return int
         */
        public function sizeStatus($status) {
            if(($status = self::getStatusCode($status)) && isset($this->countStatus[$status])) {
                return $this->countStatus[$status];
            }
            else {
                return 0;
            }
        }

        /**
         * Получениее числа сообщений, указанных типов и статусов
         *
         * @param  array|string|null $types
         * @param  array|string|null $statuses
         *
         * @return int
         */
        public function size($types = null, $statuses = null) {
            $res = array();

            $i = 0;

            if($types) {
                $types = \detemiro\take_good_array($types, true);

                foreach($types as $type) {
                    if(is_string($type) && isset($this->list[$type])) {
                        $res[$type] = $this->list[$type];
                    }
                }
            }
            else {
                $res = $this->list;
            }

            if($statuses) {
                $statuses = \detemiro\take_good_array($statuses, true);
                foreach($statuses as &$status) {
                    $status = self::getStatusCode($status);
                }

                foreach($res as $type) {
                    foreach($type as $item) {
                        if(in_array($item->status, $statuses)) {
                            $i++;
                        }
                    }
                }
            }
            else {
                foreach($res as $type) {
                    $i += count($type);
                }
            }

            return $i;
        }

        /**
         * Добавление сообщения
         *
         * Данный метод добавляет сообщение в коллектор, стандартно по типу 'system'.
         *
         * Аргументом должен являться массив с возможными ключами:
         * 
         * Ключ   | Описание
         * ------ | ----------------
         * type   | Тип сообщения
         * status | Статус сообщения
         * text   | Текст сообщения
         * title  | Заголовок
         * save   | Число показов
         *
         * *Стоит пояснить, что ключ 'save' служит для дополнительного сохранения сообщения в сессии пользователя, если save > 1, то сообщений добавиться в коллектор при следующей очистке, т.е. при обновлении страницы, при этом save уменьшится на единицу.*
         *
         * @see detemiro\messages\message
         * 
         * @param  array|object $obj
         *
         * @return bool
         */
        public function push($obj) {
            if(is_array($obj)) {
                try {
                    $custom = new message($obj);
                }
                catch(\Exception $error) {
                    return false;
                }
            }
            elseif(is_object($obj) && ($obj instanceof message)) {
                $custom = $obj;
            }
            else {
                return false;
            }

            if($custom->text || $custom->title) {
                $this->list[$custom->type][] = $custom;

                if(isset($this->countType[$custom->type])) {
                    $this->countType[$custom->type]++;
                }
                else {
                    $this->countType[$custom->type] = 1;
                }

                if(isset($this->countStatus[$custom->status])) {
                    $this->countStatus[$custom->status]++;
                }
                else {
                    $this->countStatus[$custom->status] = 1;
                }

                if($custom->save > 1 && property_exists($custom, 'session') == false && \detemiro\session_prot_start()) {
                    $custom->session = true;

                    if(isset($_SESSION['detemiro.messages']) && is_array($_SESSION['detemiro.messages'])) {
                        $_SESSION['detemiro.messages'][] = $custom;
                    }
                    else {
                        $_SESSION['detemiro.messages'] = array($custom);
                    }
                }

                return true;
            }
            else {
                return false;
            }
        }
    }
?>