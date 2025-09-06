<?php

// app/controllers/AdminUsersApiController.php

class AdminUsersApiController extends BaseController
{
    private UserModel $userModel;

    public function __construct(ViewAdmin $view)
    {
        parent::__construct($view);
        $this->userModel = new UserModel();
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function block($userId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Обновляем статус пользователя в базе данных
        $result = $this->userModel->updateUserStatus($userId, 0); // 0 для "заблокирован"

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Пользователь успешно заблокирован.']);
        } else {
            http_response_code(500); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Ошибка при блокировании пользователя.']);
        }
        exit;
    }

    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function unblock($userId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Обновляем статус пользователя в базе данных
        $result = $this->userModel->updateUserStatus($userId, 1); // 1 для "разблокирован"

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Пользователь успешно разблокирован.']);
        } else {
            http_response_code(500); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Ошибка при разблокировании пользователя.']);
        }
        exit;
    }

    /**
     * @route DELETE /admin/users/api/block/$userId
     */
    public function delete($userId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');
        
        // Обновляем статус пользователя в базе данных
        $result = $this->userModel->deleteUser($userId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Пользователь успешно удален.']);
        } else {
            http_response_code(500); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Удаление невозможно. У пользователя есть написанные посты и/или медиафайлы.']);
        }
        exit;
    }

    /**
     * @route POST /admin/users/api/create
     */
    public function create()
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Получаем JSON-тело запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Проверяем наличие необходимых данных
        $requiredFields = ['name', 'login', 'email', 'password', 'confirm_password', 'role_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => 'Все поля обязательны для заполнения.']);
                return;
            }
        }

        // Проверка совпадения паролей
        if ($data['password'] !== $data['confirm_password']) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Пароли не совпадают.']);
            return;
        }

        // Проверка уникальности логина и email
        if ($this->userModel->isLoginExists($data['login'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Логин уже занят.']);
            return;
        }
        if ($this->userModel->isEmailExists($data['email'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Email уже зарегистрирован.']);
            return;
        }
        if (!$this->userModel->isRoleExists($data['role_id'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Несуществующая роль.']);
            return;
        }

        // Хеширование пароля для безопасности
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // Попытка создать пользователя
        if ($this->userModel->createUser($data)) {
            echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Не удалось создать пользователя.']);
        }
    }

    /**
     * @route PUT /admin/users/api/edit
     */
    public function edit($userId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Проверка прав доступа: только админ или сам пользователь могут редактировать.
        $currentUserId = Auth::getUserId();
        $isAdmin = Auth::isUserAdmin();
        
        // Если текущий пользователь не админ и пытается редактировать другого пользователя,
        // или пытается редактировать ID, который не соответствует его собственному.
        if (!$isAdmin && $userId != $currentUserId) {
            // http_response_code(403); // Forbidden
            // echo json_encode(['success' => false, 'message' => 'Недостаточно прав для редактирования этого пользователя.']);
            // return;
            $this->sendErrorJsonResponse('Недостаточно прав для редактирования этого пользователя.', 403);
        }

        // Получаем JSON-тело запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $user = $this->userModel->getUser($userId);

        // Проверяем наличие необходимых данных
        $requiredFields = ['name', 'email'];
        if ($user['built_in'] === 0)
        {
            // Роль можно менять только не у системных пользователей
            // Поэтому и проверять ее имеет смысл только не у системных
            $requiredFields[] = 'role_id';
        }
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendErrorJsonResponse('Все поля обязательны для заполнения.');
            }
        }

        if (!empty($data['password']) && $data['password'] !== $data['confirm_password'])
        {
            $this->sendErrorJsonResponse('Пароли не совпадаеют.');
        }

        // Подготовка данных для обновления
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email']            
        ];

        if ($user['built_in'] === 0)
        {
            // Роль можно менять только не у системных пользователей
            $updateData['role_id'] = $data['role_id'];
        }
        
        // Если пароль был предоставлен, хешируем его и добавляем к данным
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Обновляем данные пользователя в базе данных
        $result = $this->userModel->updateUser($userId, $updateData);

        if ($result) {
            // echo json_encode(['success' => true, 'message' => 'Пользователь успешно обновлен.']);
            $this->sendSuccessJsonResponse('Пользователь успешно обновлен.');
        } else {
            // http_response_code(500); // Internal Server Error
            // echo json_encode(['success' => false, 'message' => 'Не удалось обновить пользователя.']);
            $this->sendErrorJsonResponse('Не удалось обновить пользователя.', 500);
        }
    }
}
