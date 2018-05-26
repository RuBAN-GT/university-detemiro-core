<?php
    detemiro::actions()->add(array(
        'code'     => 'cookie-session.generate',
        'function' => function() {
            if(detemiro::user()->hash) {
                $hash = detemiro::user()->hash;
            }
            else {
                $hash = \detemiro\random_hash(26, false);

                detemiro::user()->updateItem('hash', $hash);
            }

            if(isset($_POST['cookie-session-remember'])) {
                $life = (is_numeric(detemiro::config()->get('cookie-session.life'))) ? detemiro::config()->get('cookie-session.life') : 900;
            }
            else {
                $life = 0;
            }

            if($life) {
                $_COOKIE['userID']   = detemiro::user()->id;
                $_COOKIE['userHash'] = $hash;

                setcookie('userID', detemiro::user()->id, time() + $life, '/');
                setcookie('userHash', $hash, time() + $life, '/');

                if(isset($_SESSION['userID'])) {
                    unset($_SESSION['userID']);
                }
                if(isset($_SESSION['userHash'])) {
                    unset($_SESSION['userHash']);
                }
            }
            else {
                $_SESSION['userID']   = detemiro::user()->id;
                $_SESSION['userHash'] = $hash;

                \detemiro\destroy_cookie('userID');
                \detemiro\destroy_cookie('userHash');
            }
        }
    ));
?>