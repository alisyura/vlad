<?php
// app/controllers/AdminPostsApiController.php

class AdminPostsApiController extends BaseController
{
    public function checkUrl()
    {
        try
        {
            header('Content-Type: application/json');

            // 1. Проверяем, что это AJAX-запрос
            if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ запрещён.']);
                exit;
            }

            // 2. Получаем данные из тела POST-запроса
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? '';
            $csrfToken = $input['csrf_token'] ?? '';

            // 3. Проверяем CSRF-токен (предполагаем, что у вас есть функция для этого)
            if (!CSRF::validateToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['error' => 'Неверный CSRF-токен.']);
                exit;
            }

            if (empty($url)) {
                echo json_encode(['is_unique' => false]);
                return;
            }

            $postModel = new AdminPostsModel();
            // В данном случае мы не передаём ID, так как пост создаётся
            $isUnique = !$postModel->postExists(null, $url); 

            echo json_encode(['is_unique' => $isUnique]);
        }
        catch(Exception $e)
        {
            Logger::error("Ошибка при проверке URL $url" . $e->getTraceAsString());
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Ошибка при проверке URL']);
            exit;
        }
    }
}