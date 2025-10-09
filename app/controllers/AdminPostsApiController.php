<?php
// app/controllers/AdminPostsApiController.php

class AdminPostsApiController extends BaseController
{
    use JsonResponseTrait;

    private PostModelAdmin $model;
    private AdminPostsApiService $postsApiService;

    public function __construct(Request $request, PostModelAdmin $model, 
        AdminPostsApiService $postsApiService)
    {
        parent::__construct($request, null);
        $this->model = $model;
        $this->postsApiService = $postsApiService;
    }
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
     * Выполняет мягкое удаление поста/страницы по ID (через AJAX).
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

    /**
     * Проверяет что поста/страницы с переданным урлом нет, чтобы создать новый пост/страницу
     * (AJAX POST запрос)
     */
    public function checkUrl()
    {
        try
        {
            header('Content-Type: application/json');

            // Получаем данные из тела POST-запроса
            $input = json_decode(file_get_contents('php://input'), true);
            $url = $input['url'] ?? '';

            if (empty($url)) {
                $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => false]);
                return;
            }

            $postModel = new AdminPostsModel();
            // В данном случае мы не передаём ID, так как пост создаётся
            $isUnique = !$postModel->postExists(null, $url); 

            $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => $isUnique]);
        }
        catch(Exception $e)
        {
            Logger::error("Ошибка при проверке URL $url" . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при проверке URL', 403);
            exit;
        }
    }

    /**
     * Точка входа на создание нового поста/страницы (AJAX POST запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     */
    public function create($articleType)
    {
        $this->createArticle($articleType);
    }

    /**
     * Создает запись с типом из articleType
     * Вызывается по AJAX POST
     */
    private function createArticle($articleType) {
        Logger::debug("createArticle. Начало");
        
        $postData=$this->request->getJson();

        try {
            $newPostId = $this->postsApiService->createArticle($postData, $articleType);

            if ($newPostId) {
                $adminRoute = Config::get('admin.AdminRoute');
                $msgText = ($articleType == 'post' ? 'Пост успешно создан' : 'Страница успешно создана');
                $this->sendSuccessJsonResponse($msgText, 200, ['redirect' => "/$adminRoute/{$articleType}s"]);
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при создании поста.', 500);
            }
        } catch (UserDataException $e) {
            Logger::error("createArticle. ошибки заполнены. выход");
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("createArticle. сбой при создании поста/страницы", ['articleType' => $articleType]);
            $this->sendErrorJsonResponse('Сбой при создании поста/страницы.', 500);
        }

        exit;
    }

    /**
     * Точка входа на редактирование поста/страницы (AJAX PUT запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     */
    public function edit($articleType)
    {
        $this->editArticle($articleType);
    }

    /**
     * Изменяет запись с типом из articleType
     * Вызов по AJAX PUT
     */
    private function editArticle($articleType)
    {
        header('Content-Type: application/json');


        Logger::debug("editArticle. Начало");


        $json_data = file_get_contents('php://input');
        $decodedData = json_decode($json_data, true);

        $postId = filter_var($decodedData['id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim($decodedData['title'] ?? '');
        $content = $decodedData['content'] ?? '';
        $status = $decodedData['status'] ?? 'draft';
        $meta_title = trim($decodedData['meta_title'] ?? '');
        $meta_description = trim($decodedData['meta_description'] ?? '');
        $meta_keywords = trim($decodedData['meta_keywords'] ?? '');
        $excerpt = trim($decodedData['excerpt'] ?? '');
        $selectedCategories = $decodedData['categories'] ?? [];

        $selectedTags = $decodedData['tags'] ?? [];
        $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

        $thumbnailUrl = trim($decodedData['post_image_url'] ?? '');

        $adminPostsModel = new AdminPostsModel();
        if (!$adminPostsModel->postExists($postId))
        {
            Logger::debug("editArticle. post does not exists. postId={$postId}");
            $data['errors'][] = 'Пост не найден.';
        }
        if (empty($title)) {
            Logger::debug("editArticle. title empty");
            $data['errors'][] = 'Заголовок поста обязателен.';
        }

        if (!empty($data['errors'])) {
            Logger::debug("editArticle. ошибки заполнены. выход");
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Неверно заполнены поля.',
                'errors' => $data['errors']]);
            exit;
        }


        $user_id = Auth::getUserId();
        $postData = [
            'user_id' => $user_id,
            'article_type' => $articleType,
            'status' => $status,
            'title' => $title,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'excerpt' => $excerpt,
            'thumbnail_url' => $thumbnailUrl,
        ];

        $updateResult = $adminPostsModel->updatePost($postId, $postData, $selectedCategories, $tagsString);
        
        if ($updateResult) {
            $adminRoute = Config::get('admin.AdminRoute');
            $msgText = ($articleType == 'post' ? 'Пост успешно обновлен' : 'Страница успешно обновлена');
            echo json_encode(['success' => true, 
                'redirect' => "/$adminRoute/{$articleType}s/edit/{$postId}",
                'message' => $msgText]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 
                'message' => 'Произошла ошибка при создании поста.']);
        }

    }
}