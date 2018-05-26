<?php
    namespace detemiro\modules\forms;

    /**
     * Класс формы
     */
    class form {
        /**
         * Коллектор элементов
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
            $this->sortPriority($this->list);

            return $this->list;
        }

        /**
         * Массив значений, ключами являются имена элементов
         * 
         * @return array
         */
        public function data() {
            $res = array();

            foreach($this->list as $key=>$item) {
                if($item->ignore == false) {
                    $res[$key] = $item->value;
                }
            }

            return $res;
        }

        /**
         * Конструктур с добавлением элементов.
         *
         * @param array|null $items Добавляемые элементы
         */
        public function __construct(array $items = null) {
            if($items) {
                foreach($items as $item) {
                    $this->set($item);
                }
            }
        }

        /**
         * Добавление элемента в коллектор
         * 
         * @param  array $obj
         *
         * @return bool
         */
        public function set(array $obj) {
            try {
                $new = new item($obj);

                $this->list[$new->name] = $new;

                return true;
            }
            catch(\Exception $error) {
                return false;
            }
        }

        /**
         * Обновление полей элемента
         *
         * @param  string $name
         * @param  array  $par
         *
         * @return bool
         */
        public function update($name, array $par) {
            if(isset($this->list[$name])) {
                foreach($par as $key=>$value) {
                    $this->list[$name]->$key = $value;
                }

                if(isset($par['name']) && $name != $par['name']) {
                    $this->list[$par['name']] = $this->list[$name];

                    unset($this->list[$name]);
                }
            }
            else {
                return false;
            }
        }

        /**
         * Изменение значения элемента
         * 
         * @param  string $name
         * @param  mixed  $value
         *
         * @return bool
         */
        public function setValue($name, $value = null) {
            if(isset($this->list[$name])) {
                $this->list[$name]->value = $value;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Ручная установка валидности элемента
         * 
         * @param string    $name
         * @param bool|null $value
         *
         * @return bool
         */
        public function setValide($name, $value) {
            if(isset($this->list[$name])) {
                $this->list[$name]->valide = $value;

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Получение объекта элемента
         * 
         * @param  string $name
         *
         * @return \detemiro\modules\forms\item
         */
        public function get($name) {
            if(isset($this->list[$name])) {
                return $this->list[$name];
            }
            else {
                return null;
            }
        }

        /**
         * Проверка существования элемента
         * 
         * @param  string $name
         *
         * @return bool
         */
        public function exists($name) {
            return (isset($this->list[$name]));
        }

        /**
         * Число элементов
         *
         * @param  bool $all
         *
         * @return int
         */
        public function count($all = true) {
            if($all) {
                return count($this->list);
            }
            else {
                $i = 0;

                foreach($this->list as $item) {
                    if($item->ignore == false) {
                        $i++;
                    }
                }

                return $i;
            }
        }

        /**
         * Удаление элемента
         * 
         * @param  string $name
         *
         * @return bool
         */
        public function remove($name) {
            if(isset($this->list[$name])) {
                unset($this->list[$name]);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Получение значения элемента
         * 
         * @param  string $name
         *
         * @return mixed
         */
        public function getValue($name) {
            if(isset($this->list[$name])) {
                return $this->list[$name]->value;
            }
            else {
                return null;
            }
        }

        /**
         * Напечатать значение элемента
         * 
         * @param  string $name
         *
         * @return void
         */
        public function printValue($name) {
            if(isset($this->list[$name])) {
                $this->list[$name]->printValue();
            }
        }

        /**
         * Проверка валидности элемента с вызовом обработчиков
         * 
         * @param  string $name
         *
         * @return bool|null
         */
        public function validate($name) {
            if(isset($this->list[$name])) {
                return $this->list[$name]->validate();
            }
            else {
                return null;
            }
        }

        /**
         * Проверка валидности всех элементов с вызовом обработчиков
         * 
         * @return bool
         */
        public function validateAll() {
            $res = array();

            foreach($this->list as $item) {
                if($item->ignore == false) {
                    $item->validate();

                    $res[] = $item->valide;
                }
            }

            return (in_array(false, $res, true) == false);
        }

        /**
         * Получение значения поля валидности элемента
         * 
         * @param  string $name
         *
         * @return bool|null
         */
        public function check($name) {
            if(isset($this->list[$name])) {
                return $this->list[$name]->valide;
            }
            else {
                return false;
            }
        }

        /**
         * Общая проверка всех полей валидности
         * 
         * @return bool
         */
        public function checkAll() {
            $res = array();

            foreach($this->list as $item) {
                if($item->ignore == false) {
                    $res[] = $item->valide;
                }
            }

            return (in_array(false, $res, true) == false);
        }

        /**
         * Вывод элемента формы
         *
         * @param string $name
         *
         * @return void
         */
        public function printInput($name) {
            if(isset($this->list[$name])) {
                $this->list[$name]->printInput();
            }
        }

        /**
         * Вывод всех элементов формы
         *
         * @param array|string|null $list Список элементов
         *
         * @return void
         */
        public function printInputList($list = null) {
            if($list) {
                $list = \detemiro\take_good_array($list, true);
                $mode = (is_string($list[0]) && $list[0][0] == '!');

                if($mode) {
                    $list[0] = substr($list[0], 1);

                    $this->sortPriority($this->list);

                    foreach($this->list as $item) {
                        if(in_array($item->name, $list) == false && $item->hidden == false) {
                            $item->printInput();
                        }
                    }
                }
                else {
                    foreach($list as $item) {
                        if($item = $this->get($item)) {
                            $item->printInput();
                        }
                    }
                }
            }
            else {
                $this->sortPriority($this->list);

                foreach($this->list as $item) {
                    if($item->hidden == false) {
                        $item->printInput();
                    }
                }
            }
        }

        /**
         * Заполнение значений элементов по массиву (принцип set_merge)
         *
         * @param  array $source Новые значения
         *
         * @return int Количество успешно добавленных значений
         */
        public function fillIn(array $source) {
            $i = 0;

            foreach($source as $key=>$value) {
                if($this->setValue($key, $value)) {
                    $i++;
                }
            }

            return $i;
        }

        protected function sortPriority(array &$obj) {
            $keys = array_keys($obj);

            uasort($obj, function($a, $b) use($keys) {
                if($a->priority === $b->priority) {
                    $i = array_search($a->name, $keys);
                    $j = array_search($b->name, $keys);

                    return ($i >= $j);
                }
                elseif(is_numeric($a->priority) && is_numeric($b->priority)) {
                    return ($a->priority >= $b->priority);
                }
                elseif($a->priority === false || $a->priority === null || $b->priority === true) {
                    return -1;
                }
                elseif($b->priority === false || $b->priority === null || $a->priority === true) {
                    return 1;
                }
                else {
                    return 0;
                }
            });
        }
    }
?>