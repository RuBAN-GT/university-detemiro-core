<?php
    namespace detemiro;

    /**
     * Функции ядра detemiro
     *
     * Базовые функции, использумые в различных частях ядра detemiro, а также в некоторых базовых модулей.
     */

    /**
     * Транслит
     * 
     * Функция осуществляет транслит строки в латиницу.
     * Если не установлена библиотека intl, то результатом будет false.
     * 
     * @param  string      $word
     * @return string|bool
     */
    function translite($word) {
        if(class_exists('\Transliterator') && is_string($word)) {
            $transliter = \Transliterator::create("latin; NFKD; [^\u0000-\u007E] Remove; NFC");

            return $transliter->transliterate($word);
        }
        else {
            return false;
        }
    }

    /**
     * Нормализация пути
     *
     * Замена обратных слешей на обычные в Windows.
     *
     * @param  string $path
     * @return string
     */
    function norm_path($path) {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|/+|', '/', $path);

        return $path;
    }

    /**
     * Проверка на JSON
     *
     * Функция осуществляет проверку строки на JSON-формат.
     * 
     * @param  string $str
     * @return bool
     */
    function is_json($str) {
        if($str && is_string($str)) {
            $res = json_decode($str);

            return (json_last_error() == JSON_ERROR_NONE);
        }
        else {
            return false;
        }
    }

    /**
     * Декодирование JSON-структур
     * 
     * @param  string            $str
     * @param  bool              $assoc Результат будет преобразован в ассоц. массив
     * @return array|object|null
     */
    function json_decode_struct($str, $assoc = true) {
        if($str && is_string($str) && is_numeric($str) == false) {
            $res = json_decode($str, $assoc);

            if(json_last_error() == JSON_ERROR_NONE) {
                return $res;
            }
        }

        return null;
    }

    /**
     * Кодирование в JSON
     *
     * Корректное кодирование $obj в JSON (в строку Unicode).
     * 
     * @param  mixed       $obj
     * @return string|null
     */
    function json_val_encode($obj) {
        $res = null;

        if(is_string($obj)) {
            $res = $obj;
        }
        else {
            try {
                $res = json_encode($obj, JSON_UNESCAPED_UNICODE);
            }
            catch(\Exception $e) {
                $res = null;
            }
        }

        return $res;
    }

    /**
     * Чтение JSON-файла
     *
     * Данная функция считывает контент файла $path и декодирует JSON-контент, если он существует.
     * Если файла не существует/он не считывается или он не содержит JSON-строку, то результатом будет null.
     * 
     * @param  string     $path Абсолютный путь файла
     * @return array|null
     */
    function read_json($path) {        
        $content = @file_get_contents($path);
        
        return json_decode_struct($content, true);
    }

    /**
     * Создание массива
     *
     * Функция создаёт массив из аргумента $arr.
     * Если $arr - это null, то результатом будет [null], если строка, то возможно проверка по запятым и на json-формат, если это объект, то результатом будет массив с публичными полями $arr и т.д.
     * 
     * @param  mixed $arr  Исходный аргумент
     * @param  bool  $exp  Разбор строки по запятым
     * @param  bool  $json Декодировать строки, если она JSON-формата
     * @return array
     */
    function take_good_array($arr, $exp = false, $json = false) {
        if(is_array($arr) == false) {
            if(is_string($arr)) {
                $try = null;
                
                if($json && ($try = json_decode_struct($arr, true))) {
                    $arr = $try;
                }
                elseif($exp) {
                    $arr = explode(',', $arr);
                }
                else {
                    $arr = array($arr);
                }
            }
            elseif(is_object($arr)) {
                $arr = (array) $arr;
            }
            else {
                $arr = array($arr);
            }
        }
        
        return $arr;
    }

    /**
     * Проверка на структуру
     *
     * Функция проверяет, является ли $obj массивом или объектом (структурой).
     * 
     * @param  array|object $obj
     * @return bool
     */
    function is_struct($obj) {
        return (is_array($obj) || is_object($obj));
    }

    /**
     * Проверка на ассоц. массив
     *
     * Функция проверяет, является ли $obj ассоциативным массивом.
     * 
     * @param  array $arr
     * @return bool
     */
    function is_assoc_array($arr) {
        return (is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1));
    }

    /**
     * Поиск ключа
     *
     * Функция осуществляет поиск ключа $key со значением $value во вложенных структурах $obj.
     * 
     * @param  mixed        $value
     * @param  array|object $obj
     * @param  string|int   $key
     * @return int|null
     */
    function array_multi_seeach($value, $obj, $key) {
        foreach($obj as $i=>$item) {
            if(is_struct($item)) {
                $item = (array) $item;
                
                if(array_key_exists($key, $item) && $item[$key] == $value) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Подключение файла с созданием переменных по массиву
     * 
     * @param  string     $file
     * @param  array|null $args
     * @return bool
     */
    function extrclude($file, array $args = null) {
        if(file_exists($file)) {
            if($args) {
                extract($args, EXTR_PREFIX_ALL, 'det');
            }

            include($file);

            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Поиск вхождения
     *
     * Функция поиска вхождения хотя бы одного значения из первого параметра во втором.
     * Если первое значение начинается со знака '!', то результат противоположный.
     *
     * @param array $find
     * @param array $all
     *
     * @return bool
     */
    function find_struct(array $find, array $all) {
        if($find[0][0] == '!') {
            $mode = true;

            $find[0] = substr($find[0], 1);
        }
        else {
            $mode = false;
        }

        foreach($find as $item) {
            if(in_array($item, $all)) {
                return !$mode;
            }
        }

        return $mode;
    }

    /**
     * Валидация кода
     *
     * Проверяет строку на валидный формат (цифры, латиница, а также символы (_.-) с максимальной длиной от 3 до 60 символов).
     * 
     * @param  mixed $code
     * @return bool
     */
    function validate_code($code) {
        return ($code && is_string($code) && preg_match('/^[a-zA-Z0-9\_\.\-]{1,59}$/i', $code) > 0);
    }

    /**
     * Преобразование кода
     *
     * Преобразует строку по возможности в валидный формат.
     * Если исходный аргумент не строка, то результатом будет этот же аргумент.
     *
     * @see \detemiro\validate_code()
     * 
     * @param  mixed $code
     * @return mixed
     */
    function canone_code($code) {
        if($code && is_string($code)) {
            $code = str_replace(array(' ', ',', '/'), '-', $code);
            $code = substr($code, 0, 60);
        }

        return $code;
    }

    /**
     * Генерация хеш-кода
     *
     * Функция генерирует строку определённой длины, состоящей из латинских символов, а также опционально символов (!@#$%^*())
     * 
     * @param  int    $L  Длина строки
     * @param  bool   $ex Использование символов
     * @return string
     */
    function random_hash($L = 16, $ex = true) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789';

        if($ex) $chars .= '!@#$%^*()';

        $hash = '';
        $C = strlen($chars) - 1;

        while(strlen($hash) < $L) {
           $hash.= $chars[mt_rand(0, $C)];
        }

        return $hash;
    }

    /**
     * Уничтожение COOKIE
     *
     * Данная функция упрощённо стирает COOKIE-ключ $name
     * 
     * @param  string $name
     * @return bool
     */
    function destroy_cookie($name) {
        if(isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            setcookie($name, null, -1, '/');

            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Старт сессии
     *
     * Упрощённый старт сессии
     * 
     * @return  bool
     */
    function session_prot_start() {
        if(session_status() != PHP_SESSION_DISABLED) {
            if(session_status() == PHP_SESSION_NONE) {
                @session_start();

                return (session_status() == PHP_SESSION_ACTIVE);
            }
            else {
                return true;
            }
        }

        return false;
    }
?>