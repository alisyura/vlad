<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseController
{
    /**
     * Поиск меток по названию для автодополнения (POST-запрос).
     */
    public function searchTags()
    {
        // $this->checkIfUserLoggedIn();

        // // Получаем токен из заголовка AJAX-запроса
        // $csrfTokenFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // // Используем ваш существующий метод для валидации токена
        // if (!CSRF::validateToken($csrfTokenFromHeader)) {
        //     http_response_code(403); // Forbidden
        //     echo json_encode(['error' => 'Invalid CSRF token']);
        //     return;
        // }
        
        // Считываем JSON из тела запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $query = $data['q'] ?? '';
        
        if (mb_strlen($query) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        try {
            $tagsModel = new TagsModel();
            $tags = $tagsModel->searchTagsByName($query);
            
            header('Content-Type: application/json');
            echo json_encode($tags);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([]);
            Logger::error('Ошибка при поиске меток: ' . $e->getMessage());
        }
    }
}