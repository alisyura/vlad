<?php
//declare(strict_types=1);
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

    public function getPostVotes()
    {
        $posts = $_POST['posts'] ?? '';
        if (empty($posts)) {
            echo json_encode(['success' => false, 'message' => 'Нет постов']);
            exit;
        }

        try {

            $uuid = getVisitorCookie();

            $stmtVisitor = $this->db->prepare("SELECT id FROM visitors WHERE uuid = :uuid");
            $stmtVisitor->execute([':uuid' => $uuid]);
            $visitor = $stmtVisitor->fetch(PDO::FETCH_ASSOC);
            $visitorId = $visitor ? $visitor['id'] : null;

            // Убираем дубликаты и пустые значения
            $postUrls = array_unique(array_filter($posts));

            if (empty($postUrls)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Нет корректных постов'
                ]);
                exit;
            }

            // Готовим SQL-запрос
            $placeholders = implode(',', array_fill(0, count($postUrls), '?'));

            $sql = "
                SELECT 
                    p.url AS post_url,
                    p.likes_count,
                    p.dislikes_count,
                    pv.vote_type AS user_vote
                FROM posts p
                LEFT JOIN post_votes pv ON p.id = pv.post_id AND pv.visitor_id = ?
                WHERE p.url IN ($placeholders)
            ";

            $params = $postUrls;
            array_unshift($params, $visitorId); // visitor_id первым

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Формируем ответ
            echo json_encode([
                'success' => true,
                'votes' => $results
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage()
            ]);
        }
    }

    private function getOrCreateVisitorId($uuid) {
        // Уже в транзакции!
        $stmt = $this->db->prepare("SELECT id FROM visitors WHERE uuid = :uuid FOR UPDATE");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['id'];
        }

        // Создаем нового visitor
        $stmt = $this->db->prepare("INSERT INTO visitors (uuid) VALUES (:uuid)");
        $stmt->execute([':uuid' => $uuid]);

        return $this->db->lastInsertId();
    }

    public function reaction()
    {
        $postUrl = $_POST['postUrl'] ?? '';
        $voteType = $_POST['type'] ?? '';
        $uuid = getVisitorCookie();

        try {
            $this->db->beginTransaction();

            // Шаг 1: Получаем или создаём visitor_id
            $visitorId = $this->getOrCreateVisitorId($uuid);

            // Шаг 2: Проверяем, уже голосовал этот visitor за этот пост
            $stmt = $this->db->prepare("
                SELECT pv.id 
                FROM post_votes pv
                JOIN posts p ON pv.post_id = p.id
                WHERE p.url = :post_url
                AND pv.visitor_id = :visitor_id;
            ");
            $stmt->execute(
                [
                    ':post_url' => $postUrl,
                    ':visitor_id' => $visitorId
                ]);
            $existingVote = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingVote) {
                $this->db->commit();
                //return ['success' => false, 'message' => 'Вы уже голосовали за этот пост'];
                echo json_encode([
                    'success' => false,
                    'message' => 'Вы уже голосовали за этот пост'
                ]);
                exit;
            }

            // // Получаем post_id по его Url
            $stmt = $this->db->prepare("SELECT id FROM posts WHERE url = :post_url");
            $stmt->execute([':post_url' => $postUrl]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                $this->db->rollBack();
                //return ['success' => false, 'message' => 'Пост не найден'];
                echo json_encode([
                    'success' => false,
                    'message' => 'Пост не найден'
                ]);
                exit;
            }
            $postId = $post['id'];

            // Шаг 3: Добавляем новый голос
            $stmt = $this->db->prepare("
                INSERT INTO post_votes (post_id, visitor_id, vote_type, created_at, updated_at)
                SELECT :post_id, :visitor_id, :vote_type, NOW(), NOW()
                FROM dual
                WHERE NOT EXISTS (
                    SELECT 1 FROM post_votes 
                    WHERE post_id = :post_id AND visitor_id = :visitor_id
                )
             ");
            $stmt->execute(
                [
                    ':post_id' => $postId,
                    ':visitor_id' => $visitorId,
                    ':vote_type' => $voteType
                ]);

            // Шаг 4: Возвращаем обновлённые счетчики
            $stmt = $this->db->prepare("
                SELECT likes_count, dislikes_count FROM posts WHERE id = :post_id
            ");
            $stmt->execute([':post_id' => $postId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'likes' => $counts['likes_count'],
                'dislikes' => $counts['dislikes_count']
            ]);
        }
        catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            //throw $e;
            echo json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'visitorId' => $visitorId,
                'message' => $e->getMessage()
            ]);
        }
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

        
        $adminIdRow = $this->getAdminId();

        if (!$adminIdRow) {
            echo json_encode([
                'success' => false,
                'message' => 'Администратор не найден'
            ]);
            return;
        }

        $adminId = $adminIdRow['id'];

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

                if ($file['size'] > 20 * 1024 * 1024) { // 20 MB
                    echo json_encode([
                        'success' => false,
                        'message' => 'Размер файла превышает 20 Мб'
                    ]);
                    return;
                }

                // === Путь по дате ===
                $uploadBaseDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . Config::getGlobalCfg('UploadDir'). '/';
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
            // Logger:error("Ошибка при добавлении пользовательского материала",
            //     [$e->getMessage()]);

            echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при добавлении материала',
                    'error' => $e->getMessage()
                ]);
        }
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