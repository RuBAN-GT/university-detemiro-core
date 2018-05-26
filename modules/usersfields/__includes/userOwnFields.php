<?php
    namespace detemiro\modules\usersfields;

    /**
     * Класс-обёртка для установки полей текущего пользователя
     *
     * @services user.fields, usersFields
     */
    class userOwnFields {
        public function get($name) {
            return \detemiro::usersFields()->get(\detemiro::user()->id, $name);
        }

        public function set($name, $value, $handlers = true) {
            return \detemiro::usersFields()->set(\detemiro::user()->id, $name, $value, $handlers);
        }

        public function delete($name) {
            return \detemiro::usersFields()->delete(\detemiro::user()->id, $name);
        }

        public function exists($name) {
            return \detemiro::usersFields()->exists(\detemiro::user()->id, $name);
        }

        public function all() {
            return \detemiro::usersFields()->all(\detemiro::user()->id);
        }

        public function __get($name) {
            return $this->get($name);
        }
        public function __set($name, $value) {
            $this->set($name, $value);
        }
        public function __isset($name) {
            return $this->exists($name);
        }
        public function __unset($name) {
            $this->delete($name);
        }
    }