<?php
// app/controllers/BaseController.php

abstract class BaseController {
    protected $viewAdmin;
    private $adminRoute;

    public function __construct(ViewAdmin $viewAdmin)
    {
        $this->viewAdmin = $viewAdmin;
        $this->adminRoute = Config::get('admin.AdminRoute');
    }

    protected function getAdminRoute()
    {
        return $this->adminRoute;
    }

    protected function showAdminError($title, $errMsg)
    {
        $data = [
            'adminRoute' => $this->getAdminRoute(),
            'user_name' => Auth::getUserName(),
            'title' => $title,
            'error_message' => $errMsg
        ];
        $this->viewAdmin->renderAdmin('admin/errors/error_view.php', $data);
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
