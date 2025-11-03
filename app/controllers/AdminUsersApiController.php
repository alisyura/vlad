<?php

// app/controllers/AdminUsersApiController.php

class AdminUsersApiController extends BaseAdminController
{
    private UserService $userService;
    private int $loggedInUserId;
    private bool $isUserAdmin;

    public function __construct(Request $request, UserService $userService, 
        AuthService $authService, ResponseFactory $responseFactory)
    {
        parent::__construct($request, null, $responseFactory);
        $this->userService = $userService;
        $this->loggedInUserId = $authService->getUserId();
        $this->isUserAdmin = $authService->isUserAdmin();
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function block($userId): Response
    {
        try {
            $this->userService->blockUser((int)$userId); 

            return $this->renderJson('Пользователь успешно заблокирован.');
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (например, попытка заблокировать админа)
            throw new HttpException($e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);

        } catch (\UserDataException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            throw new HttpException($e->getMessage(), $statusCode, $e, HttpException::JSON_RESPONSE);

        } catch (\Throwable $e) {
            Logger::error('Ошибка при блокировании пользователя', ['userId' => $userId], $e);
            throw new HttpException('Ошибка при блокировании пользователя.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function unblock($userId): Response
    {
        try {
            $this->userService->unblockUser((int)$userId); 

            return $this->renderJson('Пользователь успешно разблокирован.');
        } catch (\UserDataException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            throw new HttpException($e->getMessage(), $statusCode, $e, HttpException::JSON_RESPONSE);

        } catch (Throwable $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при разблокировании пользователя', ['userId' => $userId], $e);
            throw new HttpException('Ошибка при разблокировании пользователя.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route DELETE /admin/users/api/block/$userId
     */
    public function delete($userId): Response
    {
        try {
            $this->userService->deleteUser((int)$userId);

            return $this->renderJson('Пользователь успешно удален.');
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (Удаление невозможно из-за связанных данных)
            throw new HttpException($e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);

        } catch (\UserDataException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            throw new HttpException($e->getMessage(), $statusCode, $e, HttpException::JSON_RESPONSE);

        } catch (\Throwable $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при удалении пользователя', ['userId' => $userId], $e);
            throw new HttpException('Ошибка при удалении пользователя.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route POST /admin/users/api/create
     */
    public function create(): Response
    {
        $inputJson = $this->getRequest()->getJson();

        try {
            $this->userService->createUser($inputJson);

            return $this->renderJson('Пользователь успешно создан.', 201);
        } catch (\InvalidArgumentException $e) {
            // Ошибка валидации (отсутствуют поля, не совпадает пароль и т.п.)
            throw new HttpException($e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);

        } catch (\UserDataException $e) {
            // Ошибка бизнес-логики (логин/email занят, несуществующая роль)
            throw new HttpException($e->getMessage(), 409, $e, HttpException::JSON_RESPONSE);

        } catch (\Throwable $e) {
            // Непредвиденная ошибка (ошибка БД, Internal Server Error)
            Logger::error('Ошибка при создании пользователя', $inputJson, $e);
            throw new HttpException('Не удалось создать пользователя.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route PUT /admin/users/api/edit
     */
    public function edit($userId): Response
    {
        // если не админ и если пытается редактировать другого пользователя,
        // то возвращаем код 403
        if (!$this->isUserAdmin && $userId != $this->loggedInUserId) {
            // 403 Forbidden: Недостаточно прав для выполнения запроса
            throw new HttpException('Недостаточно прав для редактирования этого пользователя.', 403, null, HttpException::JSON_RESPONSE);
        }

        $inputJson = $this->getRequest()->getJson();
        
        try {
            // Сервис получает ID пользователя, которого редактируем, и данные
            $this->userService->updateUser($userId, $inputJson);

            return $this->renderJson('Пользователь успешно обновлен.');
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (Неверный формат, недостающие поля, несовпадение паролей)
            throw new HttpException($e->getMessage(), 400, $e, HttpException::JSON_RESPONSE);

        } catch (\UserDataException $e) {
            // Ошибка: 409 Conflict (Email/Роль уже занята/не существует) или 404 Not Found
            // Здесь можно было бы сделать более точную обработку, но для простоты используем 409/404
            $statusCode = ($e->getCode() === 404) ? 404 : 409;
            throw new HttpException($e->getMessage(), $statusCode, $e, HttpException::JSON_RESPONSE);

        } catch (\Exception $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при блокировании пользователя', ['userId' => $userId], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Не удалось обновить пользователя.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}
