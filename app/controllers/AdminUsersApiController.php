<?php

// app/controllers/AdminUsersApiController.php

class AdminUsersApiController extends BaseController
{
    use JsonResponseTrait;
    
    private UserModel $userModel;
    private UserService $userService;
    private int $loggedInUserId;
    private bool $isUserAdmin;

    public function __construct(Request $request, UserService $userService, 
        UserModel $userModel, AuthService $authService, ?View $view = null)
    {
        parent::__construct($request, $view);
        $this->userModel = $userModel;
        $this->userService = $userService;
        $this->loggedInUserId = $authService->getUserId();
        $this->isUserAdmin = $authService->isUserAdmin();
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function block($userId)
    {
        try {
            $this->userService->blockUser((int)$userId); 

            $this->sendSuccessJsonResponse('Пользователь успешно заблокирован.');
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (например, попытка заблокировать админа)
            $this->sendErrorJsonResponse($e->getMessage(), 400);

        } catch (\UserDataException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            $this->sendErrorJsonResponse($e->getMessage(), $statusCode);

        } catch (\Throwable $e) {
            Logger::error('Ошибка при блокировании пользователя', ['userId' => $userId], $e);
            $this->sendErrorJsonResponse('Ошибка при блокировании пользователя.', 500);
        }
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function unblock($userId)
    {
        try {
            $this->userService->unblockUser((int)$userId); 

            $this->sendSuccessJsonResponse('Пользователь успешно разблокирован.');
        } catch (\RuntimeException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            $this->sendErrorJsonResponse($e->getMessage(), $statusCode);

        } catch (Throwable $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при разблокировании пользователя', ['userId' => $userId], $e);
            $this->sendErrorJsonResponse('Ошибка при разблокировании пользователя.', 500);
        }
    }

    /**
     * @route DELETE /admin/users/api/block/$userId
     */
    public function delete($userId)
    {
        try {
            $this->userService->deleteUser((int)$userId);

            $this->sendSuccessJsonResponse('Пользователь успешно удален.', 200); 
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (Удаление невозможно из-за связанных данных)
            $this->sendErrorJsonResponse($e->getMessage(), 400);

        } catch (\UserDataException $e) {
            // Ошибка: 404 Not Found (Пользователь не найден)
            $statusCode = ($e->getCode() === 404) ? 404 : 409; 
            $this->sendErrorJsonResponse($e->getMessage(), $statusCode);

        } catch (\Throwable $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при удалении пользователя', ['userId' => $userId], $e);
            $this->sendErrorJsonResponse('Ошибка при удалении пользователя.', 500);
        }
    }

    /**
     * @route POST /admin/users/api/create
     */
    public function create()
    {
        $inputJson = $this->request->getJson();

        try {
            $this->userService->createUser($inputJson);

            $this->sendSuccessJsonResponse('Пользователь успешно создан.', 201);
        } catch (\InvalidArgumentException $e) {
            // Ошибка валидации (отсутствуют поля, не совпадает пароль и т.п.)
            $this->sendErrorJsonResponse($e->getMessage(), 400);

        } catch (\UserDataException $e) {
            // Ошибка бизнес-логики (логин/email занят, несуществующая роль)
            $this->sendErrorJsonResponse($e->getMessage(), 409);

        } catch (\Throwable $e) {
            // Непредвиденная ошибка (ошибка БД, Internal Server Error)
            Logger::error('Ошибка при создании пользователя', $inputJson, $e);
            $this->sendErrorJsonResponse('Не удалось создать пользователя.', 500);
        }
    }

    /**
     * @route PUT /admin/users/api/edit
     */
    public function edit($userId)
    {
        // если не админ и если пытается редактировать другого пользователя,
        // то возвращаем код 403
        if (!$this->isUserAdmin && $userId != $this->loggedInUserId) {
            // 403 Forbidden: Недостаточно прав для выполнения запроса
            $this->sendErrorJsonResponse('Недостаточно прав для редактирования этого пользователя.', 403);
            return;
        }

        $inputJson = $this->request->getJson();
        
        try {
            // Сервис получает ID пользователя, которого редактируем, и данные
            $this->userService->updateUser($userId, $inputJson);

            $this->sendSuccessJsonResponse('Пользователь успешно обновлен.');
        } catch (\InvalidArgumentException $e) {
            // Ошибка: 400 Bad Request (Неверный формат, недостающие поля, несовпадение паролей)
            $this->sendErrorJsonResponse($e->getMessage(), 400);

        } catch (\UserDataException $e) {
            // Ошибка: 409 Conflict (Email/Роль уже занята/не существует) или 404 Not Found
            // Здесь можно было бы сделать более точную обработку, но для простоты используем 409/404
            $statusCode = ($e->getCode() === 404) ? 404 : 409;
            $this->sendErrorJsonResponse($e->getMessage(), $statusCode);

        } catch (\Exception $e) {
            // Ошибка: 500 Internal Server Error (Проблема с БД)
            Logger::error('Ошибка при блокировании пользователя', ['userId' => $userId], $e);
            $this->sendErrorJsonResponse('Не удалось обновить пользователя.', 500);
        }
    }
}
