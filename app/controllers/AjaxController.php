<?php
//declare(strict_types=1);
/**
 * Класс AjaxController отвечает за обработку AJAX-запросов,
 * связанных с взаимодействием пользователей с контентом,
 * таким как голосование за посты.
 *
 * @property Request $request Объект HTTP-запроса.
 */
class AjaxController
{
    use JsonResponseTrait;

    private $db;
    private $request;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     */
    public function __construct(Request $request)
    {
        header('Content-Type: application/json');

        // $this->db = Database::getConnection();
        $this->request = $request;
    }

    public function publish()
    {
        $content = $_POST['text'] ?? '';
        $file = $_FILES['image'] ?? null;

        
        $adminId = $this->model->getAdminId(Config::get('admin.AdminRoleName'));

        if ($adminId === null) {
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
                ':user_id' => $adminId
            ]);
            $newPostId = $this->db->lastInsertId();

            if ($file && is_uploaded_file($file['tmp_name'])) {
                $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Формат файла не поддерживается'
                    ]);
                    return;
                }

                if ($file['size'] > Config::get('upload.UploadedMaxFilesize')) { // 2 MB
                    echo json_encode([
                        'success' => false,
                        'message' => 'Размер файла превышает 2 Мб'
                    ]);
                    return;
                }

                // === Путь по дате ===
                $uploadBaseDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . Config::get('upload.UploadDir'). '/';
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
                if (!move_uploaded_file($file['tmp_name'], $uploadBaseDir . $filePath)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ошибка при сохранении файла'
                    ]);
                    return;
                }

                // === Добавляем файл в media ===
                $stmt = $this->db->prepare("
                    INSERT INTO media (
                        post_id, user_id, file_name, file_path, file_type, 
                        mime_type, file_size, uploaded_at, updated_at
                    )
                    VALUES (
                        :post_id, :user_id, :file_name, :file_path, 'image', 
                        :mime_type, :file_size, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    ':post_id' => $newPostId,
                    ':user_id' => $adminId,
                    ':file_name' => $filename = basename($filePath),
                    ':file_path' => '/assets/uploads/' . $filePath,
                    ':mime_type' => $file['type'],
                    ':file_size' => $file['size']
                ]);
            }

            $this->db->commit(); // Сохраняем всё

            echo json_encode([
                'success' => true,
                'message' => 'Материал успешно отправлен на модерацию'
            ]);
        }
        catch(Exception $e) {
            $this->db->rollBack(); // Откатываем при ошибке
            //echo __DIR__ . '/../app/core/Logger.php';
            // if (class_exists('Logger')) {
            //     echo __DIR__ . '/../app/core/Logger.php';
            //     require_once __DIR__ . '/../core/Logger.php';
            // }
            Logger::error("Ошибка при добавлении пользовательского материала",
                 [$e->getTraceAsString()]);

            echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при добавлении материала',
                    'error' => $e->getMessage()
                ]);
        }
    }

    public function getCsrfToken()
    {
        $this->sendSuccessJsonResponse('', 200, ['csrf_token' => CSRF::getToken()]);
        exit;
    }
}