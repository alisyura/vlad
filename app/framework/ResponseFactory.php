<?php

// app/framework/ResponseFactory.php

/**
 * Фабрика для создания объектов HTTP-ответа (Response).
 *
 * Предоставляет удобные методы для создания стандартных типов ответов
 * (обычный, JSON, перенаправление).
 */
class ResponseFactory
{
    /**
     * Создает новый пустой объект Response.
     *
     * @param string $content Тело ответа по умолчанию.
     * @param int $statusCode HTTP-код статуса по умолчанию.
     * @param array $headers Заголовки по умолчанию.
     * @return Response
     */
    public function createResponse(string $content = '', int $statusCode = 200, array $headers = []): Response
    {
        // Создаем и возвращаем экземпляр вашего класса Response
        return new Response($content, $statusCode, $headers);
    }

    /**
     * Создает ответ с данными в формате JSON.
     *
     * @param array $data Данные для кодирования в JSON.
     * @param int $statusCode HTTP-код статуса.
     * @return Response
     */
    public function createJsonResponse(array $data, int $statusCode = 200): Response
    {
        // Создаем новый Response и вызываем его метод json()
        return $this->createResponse()->json($data, $statusCode);
    }

    /**
     * Создает ответ для перенаправления.
     *
     * @param string $url URL для перенаправления.
     * @param int $statusCode Код статуса перенаправления (302 по умолчанию).
     * @return Response
     */
    public function createRedirectResponse(string $url, int $statusCode = 302): Response
    {
        // Создаем новый Response и вызываем его метод redirect()
        return $this->createResponse()->redirect($url, $statusCode);
    }
}