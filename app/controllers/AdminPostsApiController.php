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
     * Точка входа на удаление поста/страницы (AJAX PATCH запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     */
    public function delete($articleType)
    {
        $this->deleteArticle($articleType);
    }

    /**
     * Выполняет мягкое удаление поста/страницы по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    private function deleteArticle($articleType)
    {
        Logger::debug("deleteArticle. Начало");
        
        $postData=$this->request->getJson();

        try {
            $deleteResult = $this->postsApiService->deleteArticle($postData, $articleType);

            if ($deleteResult) {
                $this->sendSuccessJsonResponse('Пост перемещен на удаление в корзину.');
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при удалении поста.', 500);
            }
        } catch (UserDataException $e) {
            Logger::error("deleteArticle. ошибки заполнены. выход");
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("deleteArticle. сбой при удалении поста/страницы", ['articleType' => $articleType, $e->getTraceAsString()]);
            $this->sendErrorJsonResponse('Сбой при удалении поста/страницы.', 500);
        }

        exit;
    }

    /**
     * Проверяет что поста/страницы с переданным урлом нет, чтобы создать новый пост/страницу
     * (AJAX POST запрос)
     */
    public function checkUrl($articleType)
    {
        Logger::debug("checkUrl. Начало");
        
        $postData=$this->request->getJson();

        try {
            $isUnique = $this->postsApiService->checkUrl($postData, $articleType);

            $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => $isUnique]);
        } catch (UserDataException $e) {
            Logger::error("checkUrl. ошибки заполнены. выход");
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("checkUrl. сбой при проверке урла", ['articleType' => $articleType, $e->getTraceAsString()]);
            $this->sendErrorJsonResponse('Сбой при проверке урла.', 500);
        }

        exit;
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
        $postData=$this->request->getJson();

        try {
            $updateResultArr = $this->postsApiService->editArticle($postData, $articleType);
            $updateResult = $updateResultArr['updateResult'];
            $postId = $updateResultArr['postId'];

            if ($updateResult) {
                $adminRoute = Config::get('admin.AdminRoute');
                $msgText = ($articleType == 'post' ? 'Пост успешно обновлен' : 'Страница успешно обновлена');
                $this->sendSuccessJsonResponse($msgText, 200, ['redirect' => "/$adminRoute/{$articleType}s/edit/{$postId}"]);
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при обновлении поста.', 500);
            }
        } catch (UserDataException $e) {
            Logger::error("editArticle. ошибки заполнены. выход", [$e->getTraceAsString()]);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("editArticle. сбой при создании поста/страницы", ['articleType' => $articleType, $e->getTraceAsString()]);
            $this->sendErrorJsonResponse('Сбой при создании поста/страницы.', 500);
        }

        exit;
    }
}