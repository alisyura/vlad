<?php
// app/controllers/AdminPostsApiController.php

class AdminPostsApiController extends BaseAdminController
{
    private AdminPostsApiService $postsApiService;
    private AuthService $authService;

    public function __construct(Request $request, AdminPostsApiService $postsApiService, 
        ResponseFactory $responseFactory, AuthService $authService)
    {
        parent::__construct($request, null, $responseFactory);
        $this->postsApiService = $postsApiService;
        $this->authService = $authService;
    }
    /**
     * Выполняет полное удаление поста по ID из БД.
     * Ожидает DELETE-запрос с JSON: { post_id: 123 }
     */
    public function hardDelete(): Response
    {
        if (!$this->authService->isUserAdmin())
        {
            throw new HttpException('Не достаточно прав для выполнения этой операции', 403, null, HttpException::JSON_RESPONSE);
        }

        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';
        $articleType = $postData['articleType'] ?? '';

        try {
            $hardDeleteResult = $this->postsApiService->hardDelete($postData);

            if ($hardDeleteResult) {
                return $this->renderJson('Пост/страница успешно удален.');
            } else {
                throw new HttpException('Произошла ошибка при удалении поста/страницы.', 409, null, HttpException::JSON_RESPONSE);
            }
        } catch (UserDataException $e) {
            Logger::error("hardDelete. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("hardDelete. сбой при удалении поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException('Сбой при удалении поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }


    /**
     * Выполняет восстановление удаленного поста по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     */
    public function restore(): Response
    {
        Logger::debug("restore. Начало");
        
        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';
        $articleType = $postData['articleType'] ?? '';

        try {
            $restoreResult = $this->postsApiService->restore($postData);

            if ($restoreResult) {
                return $this->renderJson('Пост воостановлен.');
            } else {
                throw new HttpException('Произошла ошибка при восстановлении поста/страницы.', 409, null, HttpException::JSON_RESPONSE);
            }
        } catch (UserDataException $e) {
            Logger::error("restore. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("restore. сбой при удалении поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException('Сбой при восстановлении поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

     /**
     * Точка входа на удаление поста/страницы (AJAX PATCH запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function delete($articleType): Response
    {
        return $this->deleteArticle($articleType);
    }

    /**
     * Выполняет мягкое удаление поста/страницы по ID (через AJAX).
     * Ожидает PATCH-запрос с JSON: { post_id: 123, csrf_token: "..." }
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    private function deleteArticle($articleType): Response
    {
        Logger::debug("deleteArticle. Начало");
        
        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';

        try {
            $deleteResult = $this->postsApiService->deleteArticle($postData, $articleType);

            if ($deleteResult) {
                return $this->renderJson('Пост перемещен на удаление в корзину.');
            } else {
                throw new HttpException('Произошла ошибка при удалении поста.', 500);
            }
        } catch (UserDataException $e) {
            Logger::error("deleteArticle. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("deleteArticle. сбой при удалении поста/страницы", ['postId' => $postId,'articleType' => $articleType], $e);
            throw new HttpException('Сбой при удалении поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Проверяет что поста/страницы с переданным урлом нет, чтобы создать новый пост/страницу
     * (AJAX POST запрос)
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function checkUrl($articleType): Response
    {
        Logger::debug("checkUrl. Начало");
        
        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $url = $postData['url'] ?? 'null';

        try {
            $isUnique = $this->postsApiService->checkUrl($postData, $articleType);

            return $this->renderJson('Урл доступен', 200, ['is_unique' => $isUnique]);
        } catch (UserDataException $e) {
            Logger::error("checkUrl. ошибки заполнены. выход", ['url' => $url, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("checkUrl. сбой при проверке урла", ['url' =>$url, 'articleType' => $articleType], $e);
            throw new HttpException('Сбой при проверке урла.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Точка входа на создание нового поста/страницы (AJAX POST запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function create($articleType): Response
    {
        return $this->createArticle($articleType);
    }

    /**
     * Создает запись с типом из articleType
     * Вызывается по AJAX POST
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    private function createArticle($articleType): Response {
        Logger::debug("createArticle. Начало");
        
        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $url = $postData['url'] ?? 'null';

        try {
            $this->postsApiService->createArticle($postData, $articleType);

            $adminRoute = $this->getAdminRoute();
            $msgText = ($articleType == 'post' ? 'Пост успешно создан' : 'Страница успешно создана');

            return $this->renderJson($msgText, 200, ['redirect' => "/$adminRoute/{$articleType}s"]);
        } catch (UserDataException $e) {
            Logger::error("createArticle. ошибки заполнены. выход", ['url' => $url, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);

        } catch (Throwable $e) {
            Logger::error("createArticle. сбой при создании поста/страницы", ['url' => $url, 'articleType' => $articleType], $e);
            throw new HttpException('Сбой при создании поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Точка входа на редактирование поста/страницы (AJAX PUT запрос)
     * 
     * @param string $articleType Тип статьи (post/page).
     * @return Response
     */
    public function edit($articleType): Response
    {
        return $this->editArticle($articleType);
    }

    /**
     * Изменяет запись с типом из articleType
     * Вызов по AJAX PUT
     * @return Response
     */
    private function editArticle($articleType): Response
    {
        $postData=$this->getRequest()->getJson();

        // для логгирования в catch
        $postId = $postData['id'] ?? 'null';

        try {
            $this->postsApiService->editArticle($postData, $articleType);

            $adminRoute = $this->getAdminRoute();
            $msgText = ($articleType == 'post' ? 'Пост успешно обновлен' : 'Страница успешно обновлена');

            return $this->renderJson($msgText, 200, ['redirect' => "/$adminRoute/{$articleType}s/edit/{$postId}"]);
        } catch (UserDataException $e) {
            Logger::error("editArticle. ошибки заполнены. выход", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException($e->getMessage(), $e->getCode(), $e, HttpException::JSON_RESPONSE);
        } catch (Throwable $e) {
            Logger::error("editArticle. сбой при создании поста/страницы", ['postId' => $postId, 'articleType' => $articleType], $e);
            throw new HttpException('Сбой при создании поста/страницы.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}