<?php
    detemiro::actions()->add(array(
        'function' => function() {
            if(isset($_SESSION['userID'], $_SESSION['userHash'])) {
                unset($_SESSION['userID'], $_SESSION['userHash']);
            }
            detemiro\destroy_cookie('userID');
            detemiro\destroy_cookie('userHash');
        }
    ));
?>