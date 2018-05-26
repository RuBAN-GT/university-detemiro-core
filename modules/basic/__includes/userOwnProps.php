<?php
    namespace detemiro\modules\basic;

    /**
     * Класс-обёртка для конкретного пользователя со свойствами (properties)
     *
     * @services usersProprs, user.props
     *
     * @see \detemiro\modules\basic\usersProprs
     */
    class userOwnProps {
        public function get($family, $code) {
            return \detemiro::usersProps()->getValue(array(\detemiro::user()->id, $family, $code));
        }

        public function getFamily($family) {
            return \detemiro::usersProps()->getUserFamily(\detemiro::user()->id, $family);
        }

        public function all() {
            return \detemiro::usersProps()->getAll(\detemiro::user()->id);
        }

        public function add(array $par) {
            $par['user_id'] = \detemiro::user()->id;

            return \detemiro::usersProps()->add($par);
        }

        public function set($family, $code, $value = null) {
            return \detemiro::usersProps()->set(array(\detemiro::user()->id, $family, $code), $value);
        }

        public function update($family, $code, $value = null) {
            return \detemiro::usersProps()->updateValue(array(\detemiro::user()->id, $family, $code), $value);
        }

        public function delete($family, $code) {
            return \detemiro::usersProps()->deleteItem(array(\detemiro::user()->id, $family, $code));
        }

        public function deleteFamily($family) {
            return \detemiro::usersProps()->deleteUserFamily(\detemiro::user()->id, $family);
        }

        public function exists($family, $code) {
            return \detemiro::usersProps()->exists(array(\detemiro::user()->id, $family, $code));
        }
    }
?>
