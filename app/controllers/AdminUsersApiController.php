<?php

// app/controllers/AdminUsersApiController.php

class AdminUsersApiController extends BaseController
{
    private UserModel $userModel;

    private function getUserModel()
    {
        $this->userModel = $this->userModel ?? new UserModel();
        return $this->userModel;
    }
    /**
     * @route PATCH /admin/users/api/block/$userId
     */
    public function block($userId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Обновляем статус пользователя в базе данных
        $result = $this->getUserModel()->updateUserStatus($userId, 0); // 0 для "заблокирован"

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
        $result = $this->getUserModel()->updateUserStatus($userId, 1); // 1 для "разблокирован"

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
        $result = $this->getUserModel()->deleteUser($userId);

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
        if ($this->getUserModel()->isLoginExists($data['login'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Логин уже занят.']);
            return;
        }
        if ($this->getUserModel()->isEmailExists($data['email'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Email уже зарегистрирован.']);
            return;
        }
        if (!$this->getUserModel()->isRoleExists($data['role_id'])) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Несуществующая роль.']);
            return;
        }

        // Хеширование пароля для безопасности
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        // Попытка создать пользователя
        if ($this->getUserModel()->createUser($data)) {
            echo json_encode(['success' => true, 'message' => 'Пользователь успешно создан.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Не удалось создать пользователя.']);
        }
    }
}
