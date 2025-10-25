<?php

// app/framework/response/ResponseFactory.php

/**
 * Фабрика для создания объектов HTTP-ответа (Response).
 *
 * Предоставляет удобные методы для создания стандартных типов ответов
 * (HTML/Text, JSON, перенаправление), используя конкретные дочерние классы.
 */
class ResponseFactory
{
    /**
     * Создает базовый ответ (обычно HTML или Text).
     *
     * Использует класс HtmlResponse (или ваш основной класс, наследующий AbstractResponse).
     * @param string $content Тело ответа.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Заголовки.
     * @return HtmlResponse|Response
     */
    public function createHtmlResponse(string $content = '', int $statusCode = 200, array $headers = []): Response
    {
        // Возвращает стандартный Html
        return new HtmlResponse($content, $statusCode, $headers);
    }

    /**
     * Создает ответ с данными в формате JSON.
     *
     * Использует класс JsonResponse.
     *
     * @param array $data Данные для кодирования в JSON.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     * @return JsonResponse
     */
    public function createJsonResponse(array $data, int $statusCode = 200, array $headers = []): Response
    {
        // Класс JsonResponse сам занимается JSON-кодированием данных
        return new JsonResponse($data, $statusCode, $headers);
    }

    /**
     * Создает ответ для перенаправления.
     *
     * Использует класс RedirectResponse.
     *
     * @param string $url URL для перенаправления.
     * @param int $statusCode Код статуса перенаправления (302 по умолчанию).
     * @param array $headers Дополнительные заголовки.
     * @return RedirectResponse
     */
    public function createRedirectResponse(string $url, int $statusCode = 302, array $headers = []): Response
    {
        // Класс RedirectResponse сам устанавливает заголовок Location
        return new RedirectResponse($url, $statusCode, $headers);
    }

    /**
     * Создает ответ с данными в формате XML.
     *
     * Использует класс XmlResponse.
     *
     * @param array $data Данные для кодирования в XML.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     * @return XmlResponse
     */
    public function createXmlResponse(array $data, int $statusCode = 200, array $headers = []): Response
    {
        // Класс XmlResponse сам занимается XML-кодированием и установкой Content-Type
        return new XmlResponse($data, $statusCode, $headers);
    }
    
    /**
     * Создает произвольный экземпляр класса ответа.
     * @template T of Response
     * @param class-string<T> $className
     * @param mixed ...$args
     * @return T
     */
    /*
    public function create(string $className, ...$args): Response
    {
        return new $className(...$args);
    }
    */
}