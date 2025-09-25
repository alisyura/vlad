<?php
// app/middleware/CsrfMiddleware.php

/**
 * Класс-посредник (Middleware) для защиты от CSRF-атак.
 *
 * Проверяет наличие и валидность CSRF-токена в запросах, которые изменяют данные
 * на сервере (например, POST, PUT, DELETE). Если токен отсутствует или недействителен,
 * выполнение скрипта прерывается с HTTP-статусом 403 Forbidden.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Предоставляет метод для отправки JSON-ответов об ошибке.
     */
    use JsonResponseTrait;

    /**
     * @var Request Объект, содержащий данные текущего HTTP-запроса.
     */
    private Request $request;

    /**
     * Конструктор CsrfMiddleware.
     *
     * Внедряет зависимость Request через конструктор, обеспечивая доступ
     * к данным запроса.
     *
     * @param Request $request Объект запроса, предоставляемый фреймворком.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Обрабатывает входящий HTTP-запрос и выполняет проверку CSRF-токена.
     *
     * @param array|null $param Необязательные параметры для middleware. Не используются в этом методе.
     * @return bool Возвращает true, если проверка CSRF пройдена и выполнение должно
     * продолжиться. В случае неудачи выполнение прекращается.
     */
    public function handle(?array $param = null): bool
    {
        // Методы, которые требуют CSRF-защиты
        $unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        // Проверяем, что текущий метод запроса требует защиты
        if (!in_array(strtoupper($this->request->server('REQUEST_METHOD')), $unsafeMethods)) {
            return true;
        }

        $token = '';

        // 1. Сначала ищем токен в заголовке X-CSRF-TOKEN (для AJAX)
        $token = $this->request->server('HTTP_X_CSRF_TOKEN') ?? '';
        
        // 2. Если токена в заголовке нет, ищем его в теле запроса (для обычных форм)
        // Для PUT/PATCH/DELETE токен часто передается в теле запроса, как и для POST.
        if (empty($token) && null !== $this->request->post('csrf_token')) {
            $token = $_POST['csrf_token'];
        }

        // Если токена нет нигде, или он невалидный, прерываем выполнение
        if (empty($token) || !CSRF::validateToken($token)) {
            http_response_code(403);
            
            // Если это AJAX-запрос, возвращаем JSON
            if ((null !== $this->request->server('HTTP_X_REQUESTED_WITH')) && 
                strtolower($this->request->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest') {
                $this->sendErrorJsonResponse('Неверный CSRF-токен.', 403);
            } else {
                // Иначе делаем редирект или показываем страницу с ошибкой
                header("Location: /error?code=403");
            }
            exit;
        }

        return true;
    }
}