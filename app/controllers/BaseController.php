<?php
// app/controllers/BaseController.php

/**
 * Абстрактный базовый класс для контроллеров, предоставляющий общие свойства
 * и методы для обработки запросов в приложении.
 *
 * @abstract
 * @package App\Controllers
 */
abstract class BaseController {
    /**
     * Объект View для отображения view административной панели.
     *
     * @var View
     */
    protected $view;
    /**
     * Объект Request для получения данных из запроса.
     *
     * @var Request
     */
    protected $request;
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

    public function __construct(Request $request, ?View $view = null)
    {
        $this->view = $view;
        $this->request = $request;
        $this->adminRoute = Config::get('admin.AdminRoute');
        $this->uri = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
        $this->requestUrl = sprintf("%s/%s", rtrim($this->uri, '/'), ltrim($_SERVER['REQUEST_URI'], '/'));
    }

    protected function getAdminRoute()
    {
        return $this->adminRoute;
    }

    /**
     * Возвращает полный URL-путь текущего запроса, включая параметры.
     *
     * Например, для URL "http://vlad.local/adm/thrash/posts?rytryr=323"
     * метод вернет "/adm/thrash/posts?rytryr=323". Это полезно для
     * сохранения состояния страницы или перенаправлений.
     *
     * @return string
     */
    protected function getBasePageUrl()
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
     * Отображает страницу с ошибкой для административной панели.
     *
     * Метод подготавливает данные для представления, включая заголовок страницы,
     * сообщение об ошибке, имя текущего пользователя и базовый маршрут админки,
     * после чего рендерит шаблон `error_view.php`.
     *
     * @param string $title Заголовок страницы с ошибкой.
     * @param string $errMsg Сообщение об ошибке для отображения пользователю.
     * @return void
     */
    protected function showAdminError($title, $errMsg)
    {
        $data = [
            'adminRoute' => $this->getAdminRoute(),
            'user_name' => Auth::getUserName(),
            'title' => $title,
            'error_message' => $errMsg
        ];
        if ($this->view === null)
        {
            throw new Exception('View is null');
        }
        $this->view->renderAdmin('admin/errors/error_view.php', $data);
    }

    /**
     * Отправляет JSON-ответ.
     *
     * @param array $data Данные для отправки в виде ассоциативного массива.
     * @param int $statusCode Код HTTP-ответа.
     * @return void
     */
    protected function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Отправляет JSON-ответ с ошибкой.
     *
     * @param string $message Сообщение об ошибке.
     * @param int $statusCode Код HTTP-ответа.
     * @param array $additionalData Дополнительные данные для включения в ответ.
     * @return void
     */
    protected function sendErrorJsonResponse(string $message, int $statusCode = 400, array $additionalData = []): void
    {
        $response = array_merge(['success' => false, 'message' => $message], $additionalData);
        $this->sendJsonResponse($response, $statusCode);
    }

    /**
     * Отправляет успешный JSON-ответ.
     *
     * @param string $message Сообщение об успехе.
     * @param int $statusCode Код HTTP-ответа.
     * @param array $additionalData Дополнительные данные для включения в ответ.
     * @return void
     */
    protected function sendSuccessJsonResponse(string $message, int $statusCode = 200, array $additionalData = []): void
    {
        $response = array_merge(['success' => true, 'message' => $message], $additionalData);
        $this->sendJsonResponse($response, $statusCode);
    }
}
