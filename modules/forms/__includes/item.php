<?php
    namespace detemiro\modules\forms;

    /**
     * Элемент формы, содержащий дополнительно мета-поля (title, desc и т.д.).
     *
     * @throws \Exception Если неверно указано имя элемента.
     * @throws \Exception Если неверно указан тип элемента.
     */
    class item extends \detemiro\magicControl {
        /**
         * Тип элемента (категория для зоны экшенов)
         * 
         * @var string $type
         */
        protected $type = 'default';
        protected function __set_type($value) {
            if($value) {
                if(is_numeric($value)) {
                    $value = (string) $value;
                }

                if(is_string($value)) {
                    $value = \detemiro\canone_code($value);

                    if(\detemiro\validate_code($value)) {
                        $this->type = $value;
                    }
                }
            }
            else {
                $this->type = 'default';
            }
        }

        /**
         * Имя (код) элемента
         *
         * @var string $name
         */
        protected $name;
        protected function __set_name($value) {
            if(is_numeric($value)) {
                $value = (string) $value;
            }

            if(is_string($value)) {
                $value = \detemiro\canone_code($value);

                if(\detemiro\validate_code($value)) {
                    $this->name = $value;
                }
            }
        }
        public function zoneName($full = false) {
            $name = '';

            if($this->type) {
                $name .= $this->type;
            }
            if($full) {
                $name .= ".{$this->name}";
            }

            return $name;
        }

        /**
         * Значение элемента
         * 
         * @var mixed $value
         */
        protected $value;

        /**
         * Установка значения с вызовом зон 'forms.change.{$type}' и 'forms.change.{$type}.{$name}'
         *
         * @zones forms.change.{$type}, forms.change.{$type}.{$name}
         *
         * @param  mixed $value
         *
         * @return void
         */
        protected function __set_value($value) {
            if($common = \detemiro::actions()->getZone('forms.change.' . $this->zoneName(false))) {
                foreach($common as $action) {
                    $value = $action->make($value, $this);
                }
            }
            if($spec = \detemiro::actions()->getZone('forms.change.' . $this->zoneName(true))) {
                foreach($spec as $action) {
                    $value = $action->make($value, $this);
                }
            }

            $this->value = $value;
        }

        /**
         * Валидность значения
         * 
         * @var bool|null
         */
        protected $valide;

        /**
         * Ручное изменение валидности
         * 
         * @param  bool|null $value
         *
         * @return void
         */
        protected function __set_valide($value) {
            if($value === null) {
                $this->valide = null;
            }
            else {
                $this->valide = (bool) $value;
            }
        }

        /**
         * Смысловое имя элемента
         *
         * @var string $title
         */
        protected $title = '';
        protected function __set_title($value) {
            if($value && is_string($value)) {
                $this->title = $value;
            }
            elseif($value == null) {
                $this->title = '';
            }
        }

        /**
         * Смысловое описание элемента
         *
         * @var string $desc
         */
        protected $desc = '';
        protected function __set_desc($value) {
            if($value && is_string($value)) {
                $this->desc = $value;
            }
            elseif($value == null) {
                $this->desc = '';
            }
        }

        /**
         * Обязательность непустого значения (не null и не пустая строка)
         * 
         * @var bool $require
         */
        protected $require = false;
        protected function __set_require($value) {
            $this->require = (bool) $value;
        }

        /**
         * Инициализация
         */
        public function __construct(array $obj) {
            $this->__propUpdate($obj);

            if($this->name == null) {
                throw new \Exception('Некорретное имя элемента.');
            }
        }

        /**
         * Проверка валидности значения с вызовом зон
         *
         * @zones forms.check.{$type}, forms.check.{$type}.{$name}
         * 
         * @return bool|null
         */
        public function validate() {
            if($this->require == true && is_bool($this->value) == false && $this->value !== 0 && $this->value == false) {
                $this->valide = false;
            }
            else {
                $handler = \detemiro::actions()->makeCheckZone(
                    'forms.check.' . $this->zoneName(false), $this->value, $this
                );

                if($handler === false) {
                    $this->valide = false;
                }
                else {
                    $sub = \detemiro::actions()->makeCheckZone(
                        'forms.check.' . $this->zoneName(true), $this->value, $this
                    );

                    if($sub === false) {
                        $this->valide = false;
                    }
                    elseif($sub || $handler) {
                        $this->valide = true;
                    }
                    else {
                        $this->valide = null;
                    }
                }
            }

            return $this->valide;
        }

        /**
         * Приоритет элемента в общем списке
         *
         * @var int $priority
         */
        protected $priority = 0;
        protected function __set_priority($value) {
            if(is_numeric($value) || is_bool($value)) {
                $this->priority = $value;
            }
        }

        /**
         * Получение значения для вывода результата
         *
         * @actions forms.print.{$type}.{$name}, forms.print.{$type}
         *
         * @return string
         */
        public function getPrintedValue() {
            if($action = \detemiro::actions()->get('forms.print.' . $this->zoneName(true))) {
                return $action->make($this->value, $this);
            }
            elseif($action = \detemiro::actions()->get('forms.print.' . $this->zoneName(false))) {
                return $action->make($this->value, $this);
            }

            if(is_string($this->value) || is_numeric($this->value)) {
                return $this->value;
            }
            elseif(is_bool($this->value)) {
                return (($this->value == true) ? 'true' : 'false');
            }
            elseif(\detemiro\is_struct($this->value)) {
                return \detemiro\json_val_encode($this->value);
            }
            else {
                return '';
            }
        }

        /**
         * Вывод значения
         *
         * @return void
         */
        public function printValue() {
            echo $this->getPrintedValue();
        }

        /**
         * Печать разметки
         * Предназначено для генерации элемента формы.
         *
         * @actions forms.input.{$this->type}.{$this->name}, forms.input.{$this->type}
         *
         * @return void
         */
        public function printInput() {
            if($action = \detemiro::actions()->get('forms.input.' . $this->zoneName(true))) {
                $action->make($this);
            }
            elseif($action = \detemiro::actions()->get('forms.input.' . $this->zoneName(false))) {
                $action->make($this);
            }
            elseif($action = \detemiro::actions()->get('forms.input')) {
                $action->make($this);
            }
        }
    }
?>