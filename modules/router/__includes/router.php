<?php
    namespace detemiro\modules\router;

    /**
     * Класс роутера
     */
    class router extends \detemiro\magicControl {
        /**
         * Настройки полученные из конфигурации
         *
         * @var array $config
         */
        protected $config = array(
            'url'       => '',
            'hosts'     => '',
            'ssl'       => false,
            'preferred' => '',
            'page'      => ''
        );

        /**
         * Ключ текущей страницы
         * 
         * @var string $page
         */
        protected $page;

        /**
         * Оригинальный ключ текущей страницы
         *
         * @var string $original
         */
        protected $original;

        /**
         * Страница по-умолчанию
         *
         * @var string $default
         */
        protected $default;

        /**
         * Схемы ссылок
         *
         * @var \detemiro\modules\router\schemes $schemes
         */
        protected $schemes;

        /**
         * Аргументы, полученные при анализе шаблонов
         *
         * @var array $args
         */
        protected $args = array();

        /**
         * Предпочитаемые схемы для генерации ссылок
         *
         * @var array $preferred
         */
        protected $preferred = array();

        /**
         * Доступные хосты
         * 
         * @var array $hosts
         */
        protected $hosts = array();

        /**
         * Режим SSL
         * 
         * @var bool $ssl
         */
        protected $ssl = false;

        /**
         * Текущий хост
         * 
         * @var string $currentHost
         */
        protected $currentHost;

        /**
         * Текущая полная ссылка
         * 
         * @var string $currentURL
         */
        protected $currentURL;

        /**
         * Основная ссылка
         * 
         * @var string $url
         */
        protected $url;

        /**
         * Получение текущей ссылки
         * 
         * Безопасное получение текущей ссылки
         *
         * @param  bool $scheme Отображение протокола подключения
         * @param  bool $host   Отображения хоста
         * @param  bool $port   Отображение порта
         * @param  bool $path   Отображение URI
         * @param  bool $query  Отображение запроса
         *
         * @return string
         */
        public static function getCurrentURL($scheme = true, $host = true, $port = true, $path = true, $query = true) {
            $current = '';

            if($scheme && isset($_SERVER['REQUEST_SCHEME'])) {
                $current = $_SERVER['REQUEST_SCHEME'] . '://';
            }
            if($host) {
                if(isset($_SERVER['HTTP_HOST'])) {
                    if($port) {
                        $current .= $_SERVER['HTTP_HOST'];

                        $port = false;
                    }
                    else {
                        $pos = strrpos($_SERVER['HTTP_HOST'], ':');

                        if($pos !== false) {
                            $current .= substr($_SERVER['HTTP_HOST'], 0, $pos);
                        }
                        else {
                            $current .= $_SERVER['HTTP_HOST'];
                        }
                    }
                }
                elseif(isset($_SERVER['SERVER_NAME'])) {
                    $current .= $_SERVER['SERVER_NAME'];
                }
                elseif(isset($_SERVER['SERVER_ADDR'])) {
                    $current .= $_SERVER['SERVER_ADDR'];
                }
            }
            if($port && isset($_SERVER['SERVER_PORT'])) {
                $current .= ':' . $_SERVER['SERVER_PORT'];
            }
            if(($path || $query) && isset($_SERVER['REQUEST_URI'])) {
                if($parse = parse_url($_SERVER['REQUEST_URI'])) {
                    if($path && isset($parse['path'])) {
                        $current .= rtrim($parse['path'], '/');
                    }
                    if($query && isset($parse['query'])) {
                        $current .= '?' . $parse['query'];
                    }
                }
            }

            return $current;
        }

        /** 
         * Инициализация
         *
         * @param array $params
         */
        public function __construct(array $params = null) {
            $hostOnly = self::getCurrentURL(false, true, false, true, false);
            $hostPort = self::getCurrentURL(false, true, true, true, false);

            if($params) {
                $this->config = array_replace($this->config, $params);
            }

            //Анализ хоста
            if($this->config['hosts'] && is_string($this->config['hosts'])) {
                $this->hosts = $stack = \detemiro\take_good_array($this->config['hosts'], true);

                $find = null;
                while($stack) {
                    $item = array_shift($stack);

                    if(parse_url($item, PHP_URL_PORT)) {
                        $tmp = $hostPort;
                    }
                    else {
                        $tmp = $hostOnly;
                    }

                    if($item && strpos($tmp, $item) === 0 && ($find == null || strlen($find) > strlen($item))) {
                        $find = $item;
                    }
                }

                if($find) {
                    $this->currentHost = $find;
                }
            }
            else {
                $this->currentHost = $hostOnly;

                $this->hosts = array($this->currentHost);
            }

            //Вариант использования HTTPS
            $this->ssl = ($this->config['ssl'] && extension_loaded('openssl') && (isset($_SERVER['REQUEST_SCHEME']) == false || $_SERVER['REQUEST_SCHEME'] == 'https'));

            //Анализ URL
            if($this->config['url'] && is_string($this->config['url'])) {
                if(strpos($this->config['url'], 'http') === 0) {
                    $this->url = $this->config['url'];
                }
                elseif($this->ssl) {
                    $this->url = 'https://' . $this->config['url'];
                }
                else {
                    $this->url = 'http://' . $this->config['url'];
                }
            }
            elseif($this->ssl) {
                $this->url = 'https://' . $this->currentHost;
            }
            else {
                $this->url = 'http://' . $this->currentHost;
            }

            //Полная ссылка
            if(parse_url($this->url, PHP_URL_PORT)) {
                $this->currentURL = self::getCurrentURL(true, true, true, true, true);
            }
            else {
                $this->currentURL = self::getCurrentURL(true, true, false, true, true);
            }

            //Страница по-умолчанию
            $page = $this->config['page'];
            if($page && is_string($page)) {
                $this->default = $page;
            }
            else {
                $this->default = 'index';
            }

            //Схемы
            $this->schemes = new schemes($this->currentURL, $this->currentHost, $this->default);

            if($this->config['preferred']) {
                $this->preferred = \detemiro\take_good_array($this->config['preferred'], true);
            }
        }

        /**
         * Поиск страницы
         *
         * @actions router.detect
         *
         * Данный метод определяет текущую страницу.
         * 
         * @return void
         */
        public function detect() {
            $this->schemes->scan();

            //Дополнительный поиск в экшене
            if($page = \detemiro::actions()->make('router.detect')) {
                $this->page = $page;
            }

            //Анализ схем
            if($this->page == null) {
                if($res = $this->schemes->analize()) {
                    if($res->page) {
                        $this->page = $res->page;
                    }
                    elseif(isset($res->data['key'])) {
                        $this->page = \detemiro::pages()->makeFullKey($res->data['key']);

                        $res->page = $this->page;
                    }

                    $this->args = $res->data;
                }
            }

            $this->original = $this->page;

            //Подстановка 404 или ошибки в случае провала
            if(\detemiro::pages()->exists($this->page) == false ||$this->page == null) {
                if(\detemiro::pages()->exists('404')) {
                    $this->page = '404';
                }
                else {
                    \detemiro::messages()->push(array(
                        'title'  => 'Ошибка роутера',
                        'text'   => 'Страница не определена.',
                        'type'   => 'system',
                        'status' => 'error'
                    ));
                }
            }
        }

        /**
         * Проверка страниц
         *
         * @param  string|array $pages
         *
         * @return bool
         */
        public function checkPages($pages) {
            if($pages == null) {
                return true;
            }
            else {
                $pages = \detemiro\take_good_array($pages, true);

                foreach($pages as &$item) {
                    $item = \detemiro::pages()->makeFullKey($item);
                }

                return \detemiro\find_struct($pages, $this->page);
            }
        }

        /**
         * Редирект
         *
         * Данный метод осуществляет редирект на $link.
         * 
         * @param  string $link
         *
         * @return void
         */
        public function redirect($link) {
            @header("Location: $link");

            exit();
        }

        /**
         * Данный метод осуществляет редирект на страницу $key
         * 
         * @param  string $key    Ключ страницы
         * @param  array  $get    Дополнительный параметр
         * @param  string $scheme Предпочитаемая схема
         *
         * @return bool
         */
        public function redirectOnPage($key, array $get = null, $scheme = null) {
            $link = null;

            if($this->replace($key) && ($link = $this->getLink($key, array($key), $scheme, $get))) {
                $this->redirect($link);

                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Замена страницы
         *
         * Данный метод меняет ключ найденной страницы.
         * 
         * @param  string $key
         *
         * @return bool
         */
        public function replace($key) {
            if($page = \detemiro::pages()->get($key)) {
                $this->page = $page->key;

                return true;
            }

            return false;
        }

        /**
         * Перезагрузить страницу
         * 
         * @return void
         */
        public function reload() {
            @header("Location: {$_SERVER['PHP_SELF']}");
        }

        /**
         * Генерация ссылки по зонам и схемам
         *
         * @actions router.link
         *
         * @param  string            $key    Ключ страницы
         * @param  array|null        $args   Аргументы
         * @param  array|string|null $scheme Предпочитамый шаблон
         * @param  array|null        $get    GET-параметры
         *
         * @return string
         */
        public function getLink($key, array $args = null, $scheme = null, array $get = null) {
            if($key == null) {
                $key = $this->page;
            }
            else {
                $key = \detemiro::pages()->makeFullKey($key);
            }

            $link = '';

            //Поиск в экшенах
            if($action = \detemiro::actions()->get('router.link')) {
                if($res = $action->make()) {
                    if(is_string($res)) {
                        $link = ltrim($res, '/');
                    }
                }
            }
            //Анализ схем
            else {
                if($scheme == null) {
                    $scheme = $this->preferred;
                }

                $link = $this->schemes->linkAnalize($key, $args, $scheme);
            }

            $link = $this->url . '/' . $link;

            if($get) {
                if(parse_url($link, PHP_URL_QUERY)) {
                    $link .= '&';
                }
                else {
                    $link .= '?';
                }

                $link .= http_build_query($get);
            }

            return $link;
        }

        /**
         * Получение ссылки файла относительно пространства
         * 
         * @param  string $path
         *
         * @return string
         */
        public function getSpaceLink($path) {
            $res = $this->url;

            if(is_string($path)) {
                $res .= '/' . ltrim($path, '/');
            }

            return $res;
        }

        /**
         * Получение ссылки до файла модулей
         * 
         * Данный метод возвращает ссылку до файла относительно директории с модулями или ссылки до файла модуля, если указан $module с магической константой __FILE__.
         * 
         * @param  string $path   Относительный путь
         * @param  string $module Путь до исполняющего файла
         *
         * @return string
         */
        public function getModuleLink($path, $module = null) {
            $res = $this->url;

            if(is_string($path)) {
                if($module && is_string($module)) {
                    $module = \detemiro\norm_path($module);

                    if(strpos($module, \detemiro::space()->path . '/det-content/modules/') === 0) {
                        $module = explode('/', substr($module, strlen(\detemiro::space()->path . '/det-content/modules/')))[0];

                        $res .= "/det-content/modules/$module";
                    }
                    else {
                        return null;
                    }
                }
                else {
                    $res .= '/det-content/modules';
                }

                $res .= '/' . ltrim($path, '/');
            }

            return $res;
        }

        /**
         * Определение заголовка страницы
         *
         * Данный метод определяет заголовок страницы $key с параметром $get, используя зону 'router.title'.
         * 
         * Если результатов не было найдено или $clear == true, то станадртным значением станет имя текущей страницы.
         * 
         * @param  bool $clear
         *
         * @return string
         */
        public function getTitle($clear = false) {
            $res   = null;
            $zones = null;

            if($clear == false && ($zones = \detemiro::actions()->getZone('router.title'))) {
                foreach($zones as $action) {
                    if(($res = $action->make()) && is_string($res)) {
                        return $res;
                    }
                }
            }
            elseif($this->page && ($res = \detemiro::pages()->get($this->page))) {
                $res = $res->title;
            }

            return $res;
        }

        /**
         * Получение заголовка текущей страницы
         *
         * @return null|string
         */
        public function getPageTitle() {
            $res = null;

            if($page = \detemiro::pages()->get($this->page)) {
                if(is_string($page->title)) {
                    $res = $page->title;
                }
            }

            return $res;
        }

        /**
         * Отправка запроса
         *
         * Данный метод осуществляет отправку запроса типа POST, GET, PUT, DELETE с аргументами под выбранным агентом.
         * Для его работы необходима библиотека curl.
         *
         * @see curl_init()
         * 
         * @param  string       $url   URL получателя (если вы используете метод GET, укажите параметры или в $url, или в $body)
         * @param  array|object $body  Тело запроса
         * @param  string       $type  Тип запроса (POST, GET, PUT, DELETE)
         * @param  string       $agent Агент
         *
         * @return mixed
         */
        public static function sendRequest($url, $body = null, $type = 'POST', $agent = 'detWorker') {
            if(
                function_exists('curl_version') &&
                is_string($url) && is_string($agent) &&
                in_array($type, array('POST', 'GET', 'PUT', 'DELETE'))
            ) {
                $ch = curl_init();

                if($body) {
                    if(\detemiro\is_struct($body)) {
                        $body = http_build_query($body);
                    }
                    elseif(\detemiro\is_json($body)) {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body)
                        ));
                    }
                }

                if($type == 'GET' && $body) {
                    $url .= (parse_url($url, PHP_URL_QUERY)) ? '&' : '?';
                    $url .= $body;
                }

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                if(ini_get('open_basedir') === '') {
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                }

                curl_setopt($ch, CURLOPT_USERAGENT, $agent);

                if($type != 'GET') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
                }

                if($type != 'GET' && $body) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }

                $res = curl_exec($ch);

                curl_close($ch);

                return $res;
            }
            else {
                return null;
            }
        }
    }
?>