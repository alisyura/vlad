<?php
// app/middleware/AjaxMiddleware.php

/**
 * Класс-посредник (Middleware) для проверки AJAX-запросов.
 *
 * Этот посредник гарантирует, что запросы, которые должны быть отправлены
 * через AJAX, содержат соответствующий заголовок `X-Requested-With`.
 * Если заголовок отсутствует или имеет неверное значение, выполнение скрипта
 * прекращается с ошибкой в формате JSON.
 */
class AjaxMiddleware implements MiddlewareInterface
{
    /**
     * Позволяет отправлять JSON-ответы об ошибке.
     */
    use JsonResponseTrait;

    /**
     * @var Request Объект, содержащий данные текущего HTTP-запроса.
     */
    private Request $request;

    /**
     * Конструктор AjaxMiddleware.
     *
     * @param Request $request Объект запроса, внедряемый через конструктор.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Обрабатывает входящий HTTP-запрос.
     *
     * Проверяет наличие и значение заголовка `X-Requested-With`.
     *
     * @param array|null $param Необязательные параметры для middleware. Не используются в этом методе.
     * @return bool Возвращает `true`, если запрос является валидным AJAX-запросом.
     * В противном случае выполнение прерывается.
     */
    public function handle(?array $param = null): bool
    {
        // Если метод запроса верный, проверяем наличие AJAX-заголовка
        $http_requested_with = $this->request->server('HTTP_X_REQUESTED_WITH') ?? '';
        
        if (empty($http_requested_with) || strtolower($http_requested_with) !== 'xmlhttprequest') {
            $this->sendErrorJsonResponse('Неверный формат запроса.', 403);
            exit;
        }
        
        return true;
    }
}