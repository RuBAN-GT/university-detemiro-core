<?php
    namespace detemiro\modules\loginAndPass;

    class user extends \detemiro\modules\basic\user {
        public function __construct(\detemiro\modules\database\dbActions $db, \detemiro\modules\loginAndPass\users $users, \detemiro\modules\basic\groups $groups) {
            parent::__construct($db, $users, $groups);
        }
        
        /**
         * Авторизация по паролю
         *
         * @zones loginAndPass.user.signInByPass.success
         * 
         * @param  int    $ID
         * @param  string $password
         * @param  bool   $md5
         *
         * @return bool
         */
        public function signInByPass($ID, $password, $md5 = true) {
            if($this->usersReg->checkPassword($ID, $password, $md5)) {
                if($this->init($ID)) {
                    \detemiro::actions()->makeZone('loginAndPass.user.signInByPass.success');

                    return true;
                }
            }

            return false;
        }
        
        /**
         * Авторизация по ID или логину и паролю
         * 
         * @param  string|int $code
         * @param  string     $password
         * @param  bool       $md5
         *
         * @return bool
         */
        public function signInByLoginAndPass($code, $password, $md5 = true) {
            if(is_numeric($code) == false) {
                $code = $this->usersReg->getIdByLogin($code);
            }
            
            if($code) {
                return $this->signInByPass($code, $password, $md5);
            }
            else {
                return false;
            }
        }
    }
?>