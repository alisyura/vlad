<?php

class AjaxController
{
    private $db;

    public function __construct()
    {
        header('Content-Type: application/json');

        $dbHost = Config::getDbHost('DB_HOST');
        $dbName = Config::getDbHost('DB_NAME');
        $dbUser = Config::getDbHost('DB_USER');
        $dbPass = Config::getDbHost('DB_PASS');

        $this->db = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
    }

    public function publish()
    {
        // if (!$this->isAjaxRequest()) {
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        //     exit;
        // }

        $content = $_POST['text'] ?? '';
        $file = $_FILES['image'] ?? null;

        
        $adminId = $this->getAdminId();

        if (!$adminId) {
            echo json_encode([
                'success' => false,
                'message' => 'Администратор не найден'
            ]);
            return;
        }


        // === Генерируем заголовок и дату ===
        $currentDate = date('Y-m-d H:i:s');
        $title = "Пост от " . date('d.m.Y');

        try {
            $this->db->beginTransaction();

            // === Вставляем пост со статусом pending ===
            $stmt = $this->db->prepare("
                INSERT INTO posts (
                    url, title, content, user_id, status, article_type, created_at, updated_at
                ) VALUES (:url, :title, :content, :user_id, 'pending', 'post', NOW(), NOW())
            ");
            $stmt->execute([
                ':url' => transliterate('Предложенный материал ' . $currentDate),
                ':title' => $title, 
                ':content' => $content, 
                ':user_id' => $adminId['id']
            ]);
            $newPostId = $this->db->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Материал успешно отправлен на модерацию',
                'content' => $content,
                'image' => $file,
                'admId' => $adminId['id'],
                'newpostId' => $newPostId
            ]);

            $this->db->commit(); // Сохраняем всё
        }
        catch(Exception $e) {
            $this->db->rollBack(); // Откатываем при ошибке
            //echo __DIR__ . '/../app/core/Logger.php';
            // if (class_exists('Logger')) {
            //     echo __DIR__ . '/../app/core/Logger.php';
            //     require_once __DIR__ . '/../core/Logger.php';
            // }
            // Logger:error("Ошибка при добавлении пользовательского материала",
            //     [$e->getMessage()]);

            echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при добавлении материала',
                    'error' => $e->getMessage()
                ]);
        }
    }

    public function publish1()
    {
        // $content = $_POST['text'] ?? '';
        // $file = $_FILES['image'] ?? null;

        // === Получаем ID администратора ===
        // $stmt = $this->pdo->prepare("
        //     SELECT u.id 
        //     FROM users u
        //     JOIN roles r ON u.role_id = r.id
        //     WHERE r.name = 'Administrator'
        //     ORDER BY u.id ASC
        //     LIMIT 1
        // ");
        // $stmt->execute();
        // $admin = $stmt->fetch();

        // if (!$admin) {
        //     echo json_encode([
        //         'success' => false,
        //         'message' => 'Администратор не найден'
        //     ]);
        //     return;
        // }

        // // === Генерируем заголовок и дату ===
        // $currentDate = date('Y-m-d H:i:s');
        // $title = "Пост от " . date('d.m.Y');

        // // === Вставляем пост со статусом pending ===
        // $stmt = $this->pdo->prepare("
        //     INSERT INTO posts (
        //         title, content, user_id, status, article_type, created_at, updated_at
        //     ) VALUES (?, ?, ?, 'pending', 'post', NOW(), NOW())
        // ");

        // $stmt->execute([$title, $content, $admin['id']]);
        // $postId = $this->pdo->lastInsertId();

        // === Обработка изображения ===
        if ($file && is_uploaded_file($file['tmp_name'])) {
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Формат файла не поддерживается'
                ]);
                return;
            }

            if ($file['size'] > 20 * 1024 * 1024) { // 20 MB
                echo json_encode([
                    'success' => false,
                    'message' => 'Размер файла превышает 20 Мб'
                ]);
                return;
            }

            // === Путь по дате ===
            $uploadBaseDir = __DIR__ . '/../../public/assets/uploads/';
            $year = date('Y');
            $month = sprintf("%02d", (int)date('m'));
            $day = sprintf("%02d", (int)date('d'));

            $uploadDir = $uploadBaseDir . "$year/$month/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // создаем папку, если её нет
            }

            // === Имя файла ===
            $originalName = basename($file['name']);
            $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = "$day" . '_' . transliterate(pathinfo($originalName, PATHINFO_FILENAME));
            $filePath = "$year/$month/" . "$fileName.$fileExt";

            // Если файл существует — добавляем суффикс _1, _2...
            $counter = 1;
            while (file_exists(__DIR__ . "/../../public/assets/uploads/$filePath")) {
                $filePath = "$year/$month/" . "$fileName" . "_$counter.$fileExt";
                $counter++;
            }

            // === Перемещаем файл ===
            if (!move_uploaded_file($file['tmp_name'], __DIR__ . "/../../public/assets/uploads/$filePath")) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при сохранении файла'
                ]);
                return;
            }

            // === Добавляем файл в media ===
            $stmt = $this->pdo->prepare("
                INSERT INTO media (
                    post_id, user_id, file_name, file_path, file_type, mime_type, file_size, uploaded_at, updated_at
                ) VALUES (?, ?, ?, ?, 'image', ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $postId,
                $admin['id'],
                $originalName,
                '/' . $filePath,
                $file['type'],
                $file['size']
            ]);
        }

        // === Ответ клиенту ===
        echo json_encode([
            'success' => true,
            'message' => 'Материал успешно отправлен на модерацию',
            'redirect' => "/post/$postId"
        ]);
    }

    private function getAdminId()
    {
        // === Получаем ID администратора ===
        $stmt = $this->db->prepare("
            SELECT u.id 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'Administrator'
            ORDER BY u.id ASC
            LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }

    protected function isAjaxRequest()
    {
        return (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }
}