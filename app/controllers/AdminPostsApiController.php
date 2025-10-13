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
        $postData=$this->request->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';
        $articleType = $postData['articleType'] ?? '';

        try {
            $hardDeleteResult = $this->postsApiService->hardDelete($postData);

            if ($hardDeleteResult) {
                $this->sendSuccessJsonResponse('Пост/страница успешно удален.');
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при удалении поста/страницы.', 409);
            }
        } catch (UserDataException $e) {
            Logger::error("hardDelete. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("hardDelete. сбой при удалении поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse('Сбой при восстановлении поста/страницы.', 500);
        }

        exit;
    }


    /**
     * Выполняет восстановление удаленного поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function restore()
    {
        Logger::debug("restore. Начало");
        
        $postData=$this->request->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';
        $articleType = $postData['articleType'] ?? '';

        try {
            $restoreResult = $this->postsApiService->restore($postData);

            if ($restoreResult) {
                $this->sendSuccessJsonResponse('Пост воостановлен.');
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при восстановлении поста/страницы.', 409);
            }
        } catch (UserDataException $e) {
            Logger::error("restore. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("restore. сбой при удалении поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse('Сбой при восстановлении поста/страницы.', 500);
        }

        exit;
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

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';

        try {
            $deleteResult = $this->postsApiService->deleteArticle($postData, $articleType);

            if ($deleteResult) {
                $this->sendSuccessJsonResponse('Пост перемещен на удаление в корзину.');
            } else {
                $this->sendErrorJsonResponse('Произошла ошибка при удалении поста.', 500);
            }
        } catch (UserDataException $e) {
            Logger::error("deleteArticle. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("deleteArticle. сбой при удалении поста/страницы", ['postId' => $postId,'articleType' => $articleType], $e);
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

        // для логгирования в catch
        $url = $postData['url'] ?? 'null';

        try {
            $isUnique = $this->postsApiService->checkUrl($postData, $articleType);

            $this->sendSuccessJsonResponse('Урл доступен', 200, ['is_unique' => $isUnique]);
        } catch (UserDataException $e) {
            Logger::error("checkUrl. ошибки заполнены. выход", ['url' => $url, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("checkUrl. сбой при проверке урла", ['url' =>$url, 'articleType' => $articleType], $e);
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

        // для логгирования в catch
        $url = $postData['url'] ?? 'null';

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
            Logger::error("createArticle. ошибки заполнены. выход", ['url' => $url, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("createArticle. сбой при создании поста/страницы", ['url' => $url, 'articleType' => $articleType], $e);
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

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';

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
            Logger::error("editArticle. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode(), $e->getValidationErrors());
        } catch (Throwable $e) {
            Logger::error("editArticle. сбой при создании поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            $this->sendErrorJsonResponse('Сбой при создании поста/страницы.', 500);
        }

        exit;
    }
}