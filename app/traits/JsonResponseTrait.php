<?php

// app/traits/JsonResponseTrait.php

/**
 * Trait для отправки json ответов
 *
 */
trait JsonResponseTrait
{
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
        header('Content-Type: application/json', true);
        echo json_encode($data);
        exit;
    }

    /**
     * Отправляет JSON-ответ с ошибкой.
     *
     * @param string|array $message Сообщение об ошибке или массив ошибок.
     * @param int $statusCode Код HTTP-ответа.
     * @param array $additionalData Дополнительные данные для включения в ответ.
     * @return void
     */
    protected function sendErrorJsonResponse(string|array $message, int $statusCode = 400, 
        array $additionalData = []): void
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
    protected function sendSuccessJsonResponse(string $message, int $statusCode = 200, 
        array $additionalData = []): void
    {
        $response = array_merge(['success' => true, 'message' => $message], $additionalData);
        $this->sendJsonResponse($response, $statusCode);
    }
}