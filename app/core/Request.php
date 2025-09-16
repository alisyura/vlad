<?php

// app/core/Request.php
class Request
{
    private $get;
    private $post;
    private $files;
    private $server;
    private $cookies;
    private $headers;
    private $body;
    private $method;
    private $uri;
    private $ip;
    private $scheme;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getAllHeaders();
        $this->body = file_get_contents('php://input');
        $this->method = $this->getMethod();
        $this->uri = $this->getUri();
        $this->ip = $this->getClientIp();
        $this->scheme = $_SERVER['REQUEST_SCHEME'] ?? 
             (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
    }

    /**
     * Получить базовый URL (домен + порт если нужно)
     * Базовый URI (Uniform Resource Identifier) текущего запроса.
     * Адрес домена, включая схему. Пример: http://vlad.local
     */
    public function getBaseUrl()
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $this->scheme . '://' . $host;
    }

    /**
     * URL (Uniform Resource Locator) текущего запроса,
     * включая параметры запроса (query string).
     */
    public function getRequestUrl()
    {
        return sprintf("%s/%s", rtrim($this->getBaseUrl(), '/'), ltrim($this->server('REQUEST_URI'), '/'));
    }

    public function getBasePageUrl()
    {
        // Получаем полный URL-путь с параметрами
        $fullUrl = $_SERVER['REQUEST_URI'];
        
        // Разбиваем URL на части: путь и параметры запроса
        $urlParts = parse_url($fullUrl);
        $path = $urlParts['path'] ?? '';

        // Используем регулярное выражение для удаления сегмента пагинации.
        // Выражение /p\d+$/ соответствует "/p", за которым следуют одна или
        // несколько цифр, и указывает, что этот сегмент должен быть в самом конце пути.
        return preg_replace('/\/p\d+$/', '', $path);
    }

    /**
     * Получить все заголовки
     */
    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Получить метод запроса
     */
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Получить URI
     */
    public function getUri()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        // Убираем query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        return rawurldecode($uri);
    }

    /**
     * Получить параметр из JSON тела запроса
     */
    public function json($key = null, $default = null)
    {
        $data = $this->getJson();
        
        if ($key === null) {
            return $data ?? [];
        }
        
        return $data[$key] ?? $default;
    }

    /**
     * Получить параметр из любого источника (GET, POST, JSON)
     * Аналог Laravel input()
     */
    public function input($key = null, $default = null)
    {
        // Сначала проверяем JSON (для API)
        $jsonData = $this->getJson();
        if ($jsonData !== null && array_key_exists($key, $jsonData)) {
            return $jsonData[$key];
        }
        
        // Затем POST
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        
        // Затем GET
        if (array_key_exists($key, $this->get)) {
            return $this->get[$key];
        }
        
        return $default;
    }

    /**
     * Получить все данные из запроса (GET + POST + JSON)
     */
    public function all()
    {
        $jsonData = $this->getJson() ?? [];
        return array_merge($this->get, $this->post, $jsonData);
    }

    /**
     * Проверить наличие параметра в любом источнике
     */
    public function has($key)
    {
        $jsonData = $this->getJson() ?? [];
        return array_key_exists($key, $this->get) || 
            array_key_exists($key, $this->post) || 
            array_key_exists($key, $jsonData);
    }

    /**
     * Получить только указанные параметры
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->input($key);
        }
        
        return $results;
    }

    /**
     * Получить IP клиента
     */
    public function getClientIp()
    {
        return $this->server['HTTP_CLIENT_IP'] 
            ?? $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }

    /**
     * Получить GET параметр
     */
    public function get($key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Получить все GET параметры
     */
    public function allGet()
    {
        return $this->get;
    }

    /**
     * Получить POST параметр
     */
    public function post($key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Получить все POST параметры
     */
    public function allPost()
    {
        return $this->post;
    }

    /**
     * Получить файл
     */
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Получить все файлы
     */
    public function allFiles()
    {
        return $this->files;
    }

    /**
     * Получить cookie
     */
    public function cookie($key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Получить заголовок
     */
    public function header($key, $default = null)
    {
        $key = strtolower($key);
        foreach ($this->headers as $headerKey => $value) {
            if (strtolower($headerKey) === $key) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Получить все заголовки
     */
    public function allHeaders()
    {
        return $this->headers;
    }

    /**
     * Получить тело запроса
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Получить тело запроса как массив (для JSON)
     */
    public function getJson()
    {
        return json_decode($this->body, true);
    }

    /**
     * Получить значение из server
     */
    public function server($key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Проверить, является ли запрос AJAX
     */
    public function isAjax()
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) 
            && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Проверить метод запроса
     */
    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Получить полный URL
     */
    public function getFullUrl()
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $port = $this->server['SERVER_PORT'] ?? '';
        
        // Добавляем порт только если он нестандартный
        $port = ($port === '80' && $this->scheme === 'http') || 
            ($port === '443' && $this->scheme === 'https') ? '' : ':' . $port;
        
        return $this->scheme . '://' . $host . $port . $this->uri;
    }
        
    /**
     * Получить user agent
     */
    public function getUserAgent()
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Получить referer
     */
    public function getReferer()
    {
        return $this->server['HTTP_REFERER'] ?? '';
    }

    /**
     * Проверить, является ли запрос безопасным (HTTPS)
     */
    public function isSecure()
    {
        return isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    /**
     * Магический метод для удобного доступа к GET параметрам
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Проверить существование GET параметра
     */
    public function __isset($name)
    {
        return isset($this->get[$name]);
    }
}