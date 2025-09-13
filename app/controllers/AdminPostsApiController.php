<?php
// app/controllers/AdminPostsApiController.php

class AdminPostsApiController extends BaseController
{
    /**
     * Выполняет полное удаление поста по ID из БД.
     * Ожидает DELETE-запрос с JSON: { post_id: 123 }
     */
    public function hardDelete()
    {
        // 1. Считываем и проверяем ID
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);
        if (!is_numeric($postId)) {
            $this->sendErrorJsonResponse('Неверный ID поста.');
            return;
        }

        try {
            // 2. Создаем экземпляр модели
            $adminPostsModel = new AdminPostsModel();

            // 3. Проверяем, существует ли пост, который нужно удалить.
            // Здесь нужно проверять, что пост имеет статус 'deleted' перед окончательным удалением
            $postExists = $adminPostsModel->postExists(postId: $postId, status: AdminPostsModel::STATUS_DELETED);
            if (!$postExists) {
                $this->sendErrorJsonResponse('Пост не найден или не имеет статуса "удалён".', 404);
                return;
            }

            // 4. Вызываем метод полного удаления
            $isDeleted = $adminPostsModel->hardDeletePost($postId);

            if ($isDeleted) {
                $this->sendSuccessJsonResponse('Пост полностью удален.');
            } else {
                $this->sendErrorJsonResponse('Пост не найден или произошла ошибка при удалении.', 500);
            }
        } catch (Exception $e) {
            Logger::error("Ошибка при полном удалении поста $postId: " . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка сервера при удалении поста.', 500);
        }
    }


    /**
     * Выполняет восстановление удаленного поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function restore()
    {
        // Считываем JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);

        // Проверка ID
        if (!is_numeric($postId)) {
            $this->sendErrorJsonResponse('Неверный ID поста.');
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $post = $adminPostsModel->postExists(postId: (int)$postId, status: AdminPostsModel::STATUS_DELETED);

            if (!$post) {
                $this->sendErrorJsonResponse('Пост не найден', 404);
                return;
            }

            // Помечаем пост как черновик
            $admPostsModel = new AdminPostsModel();
            $admPostsModel->setPostStatus($postId, AdminPostsModel::STATUS_DRAFT);

            $this->sendSuccessJsonResponse('Пост успешно восстановлен.');
        } catch (Exception $e) {
            Logger::error("Ошибка при восстановлении поста $postId: " . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при восстановлении поста', 500);
        }
    }

    /**
     * Выполняет мягкое удаление поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function deletePost()
    {
        // Считываем JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);

        // Проверка ID
        if (!is_numeric($postId)) {
            $this->sendErrorJsonResponse('Неверный ID поста.');
            return;
        }

        try {
            $adminPostsModel = new AdminPostsModel();
            $post = $adminPostsModel->postExists((int)$postId);

            if (!$post) {
                $this->sendErrorJsonResponse('Пост не найден', 404);
                return;
            }

            // Помечаем пост как удалённый
            $admPostsModel = new AdminPostsModel();
            $admPostsModel->setPostStatus($postId, AdminPostsModel::STATUS_DELETED);

            $this->sendSuccessJsonResponse('Пост успешно удалён.');
        } catch (Exception $e) {
            Logger::error("Ошибка при удалении поста $postId: " . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при удалении поста', 500);
        }
    }

    public function checkUrl()
    {
        try
        {
            header('Content-Type: application/json');

            // Получаем данные из тела POST-запроса
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? '';

            if (empty($url)) {
                // echo json_encode(['is_unique' => false]);
                $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => false]);
                return;
            }

            $postModel = new AdminPostsModel();
            // В данном случае мы не передаём ID, так как пост создаётся
            $isUnique = !$postModel->postExists(null, $url); 

            // echo json_encode(['is_unique' => $isUnique]);
            $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => $isUnique]);
        }
        catch(Exception $e)
        {
            Logger::error("Ошибка при проверке URL $url" . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при проверке URL', 403);
            exit;
        }
    }
}