<?php
    detemiro::actions()->add(array(
        'code'     => 'cookie-session.checkData',
        'function' => function() {
            $success = 0;

            if(\detemiro\session_prot_start() && isset($_SESSION['userID'], $_SESSION['userHash'])) {
                $ID   = $_SESSION['userID'];
                $hash = $_SESSION['userHash'];

                $success = 1;
            }
            elseif(isset($_COOKIE['userID'], $_COOKIE['userHash'])) {
                $ID   = $_COOKIE['userID'];
                $hash = $_COOKIE['userHash'];

                $success = 2;
            }

            if($success && detemiro::user()->signInByHash($ID, $hash, true) == false) {
                if($success == 1) {
                    unset($_SESSION['userID'], $_SESSION['userHash']);
                }
                else {
                    detemiro\destroy_cookie('userID');
                    detemiro\destroy_cookie('userHash');
                }
            }
        }
    ));
?>