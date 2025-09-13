<?php

// app/core/Request.php

class Request
{
    /**
     * Базовый маршрут (route) для доступа к административной панели.
     * Используется для формирования правильных URL.
     *
     * @var string
     */
    private $adminRoute;
    /**
     * Базовый URI (Uniform Resource Identifier) текущего запроса.
     * Адрес домена, включая схему. Пример: http://vlad.local
     *
     * @var string
     */
    protected $uri;
    /**
     * URL (Uniform Resource Locator) текущего запроса,
     * включая параметры запроса (query string).
     *
     * @var string
     */
    protected $requestUrl;

    public function __construct()
    {
        $this->adminRoute = Config::get('admin.AdminRoute');
        $this->uri = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
        $this->requestUrl = sprintf("%s/%s", rtrim($this->uri, '/'), ltrim($_SERVER['REQUEST_URI'], '/'));
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    public function getAdminRoute()
    {
        return $this->adminRoute;
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
}