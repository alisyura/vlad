<?php
//declare(strict_types=1);
class AjaxController
{
    private $db;
    private $request;

    public function __construct(Request $request)
    {
        header('Content-Type: application/json');

        $this->db = Database::getConnection();
        $this->request = $request;
    }

    private function getVisitorIdForUUID($uuid)
    {
        $stmtVisitor = $this->db->prepare("SELECT id FROM visitors WHERE uuid = :uuid");
        $stmtVisitor->execute([':uuid' => $uuid]);
        $visitor = $stmtVisitor->fetch(PDO::FETCH_ASSOC);
        return $visitor ? $visitor['id'] : null;
    }

    public function getPostVotes()
    {
        $json_data = file_get_contents('php://input');
        // Декодируем JSON-строку в ассоциативный массив PHP
        $post_data = json_decode($json_data, true);

        $posts = $post_data['posts'] ?? '';
        if (empty($posts)) {
            echo json_encode(['success' => false, 'message' => 'Нет постов']);
            exit;
        }

        try {

            $uuid = getVisitorCookie();

            $visitorId=$this->getVisitorIdForUUID($uuid);

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
            Logger::error('getPostVotes. Ошибка получения голосов постов. '.$e->getTraceAsString());
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

    /**
     * Голосование за пост
     */
    public function reaction()
    {
        $json_data = file_get_contents('php://input');
        // Декодируем JSON-строку в ассоциативный массив PHP
        $post_data = json_decode($json_data, true);

        $postUrl = $post_data['postUrl'] ?? '';
        $voteType = $post_data['type'] ?? '';
        $uuid = getVisitorCookie();

        Logger::debug("reaction. postUrl=".$postUrl.", voteType=".$voteType);

        try {
            $this->db->beginTransaction();

            // Шаг 1: Получаем или создаём visitor_id
            $visitorId = $this->getOrCreateVisitorId($uuid);

            Logger::debug("reaction. голосовал ли visitor=".$visitorId." за пост");
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
            Logger::debug("reaction. existingVote=".$existingVote);

            if ($existingVote) {
                Logger::debug("reaction. голосовал. выход");
                $this->db->commit();
                //return ['success' => false, 'message' => 'Вы уже голосовали за этот пост'];
                echo json_encode([
                    'success' => false,
                    'message' => 'Вы уже голосовали за этот пост'
                ]);
                exit;
            }

            Logger::debug("reaction. Получаем post_id по его Url=".$postUrl);

            // Получаем post_id по его Url
            $stmt = $this->db->prepare("SELECT id FROM posts WHERE url = :post_url");
            $stmt->execute([':post_url' => $postUrl]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                Logger::debug("reaction. Пост не найден");
                $this->db->rollBack();

                echo json_encode([
                    'success' => false,
                    'message' => 'Пост не найден'
                ]);
                exit;
            }
            $postId = $post['id'];
            Logger::debug("reaction. post_id=".$postId);

            // Шаг 3: Добавляем новый голос
            Logger::debug("reaction. Добавляем новый голос. ".
                ':post_id='. $postId.':visitor_id='.$visitorId.':vote_type='.$voteType);

            $stmt = $this->db->prepare('INSERT IGNORE INTO post_votes 
                        (post_id, visitor_id, vote_type, created_at, updated_at)
                    VALUES (:post_id, :visitor_id, :vote_type, NOW(), NOW())');
            $stmt->execute(
                [
                    ':post_id' => $postId,
                    ':visitor_id' => $visitorId,
                    ':vote_type' => $voteType
                ]);

            // Шаг 4: Возвращаем обновлённые счетчики
            Logger::debug("reaction. Возвращаем обновлённые счетчики. ".':post_id='.$postId);
            $stmt = $this->db->prepare("
                SELECT likes_count, dislikes_count FROM posts WHERE id = :post_id
            ");
            $stmt->execute([':post_id' => $postId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            Logger::debug("reaction. commit");
            $this->db->commit();

            $res = json_encode([
                'success' => true,
                'likes' => $counts['likes_count'],
                'dislikes' => $counts['dislikes_count']
            ]);
            Logger::debug("reaction. res=".$res);
            echo $res;
        }
        catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $res=json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'visitorId' => $visitorId,
                'message' => $e->getMessage()
            ]);
            Logger::error("reaction. Ошибка при голосовании. res=".$res.', '.$e->getTraceAsString());
            echo $res;
        }
    }

    public function publish()
    {
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

    public function sendMsg()
    {
        $msg_name = trim($_POST['name'] ?? '');
        $msg_email = trim($_POST['email'] ?? '');
        $msg_title = trim($_POST['title'] ?? '');
        $msg_text = trim($_POST['text'] ?? '');
        $file = $_FILES['image'] ?? null;

        $errors = [];

        // Проверка имени
        if (empty($msg_name)) {
            $errors[] = 'Имя не может быть пустым';
        }

        // Проверка темы
        if (empty($msg_title)) {
            $errors[] = 'Тема не может быть пустой';
        }

        // Проверка текста сообщения
        $text_len = mb_strlen($msg_text, 'UTF-8');
        if ($text_len < 10) {
            $errors[] = 'Сообщение должно содержать минимум 10 символов';
        }
        if ($text_len > 5000) {
            $errors[] = 'Сообщение не может превышать 5000 символов';
        }

        // Проверка email
        if (!validateEmail($msg_email)) {
            $errors[] = 'Некорректный адрес электронной почты';
        }
        
        if (count($errors)>0) {
            echo json_encode([
                'success' => false,
                'message' => $errors
            ]);
            return;
        }

         // === Настройки отправки ===
        $to = Config::get('admin.AdminEmail');
        $subject = "Сообщение с сайта от пользователя: " . htmlspecialchars($msg_title);
        $from = "From: $msg_email\r\n";
        $from .= "Reply-To: $msg_email\r\n";
        $from .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // === Тело письма ===
        $message = "Имя: $msg_name\n";
        $message .= "Email: $msg_email\n";
        $message .= "Тема: $msg_title\n\n";
        $message .= "Сообщение:\n$msg_text\n\n";
        $message .= "-- Конец сообщения --";

        // === Отправка ===
        if (mail($to, $subject, $message, $from)) {
            echo json_encode([
                'success' => true,
                'message' => 'Ваше сообщение успешно отправлено'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => ['Ошибка при отправке сообщения']
            ]);
        }
    }

    public function searchTags()
    {
        // $tagName = trim($_POST['name']) ?? '';

        $data = json_decode(file_get_contents('php://input'), true);
        $tagName = $data['name'] ?? '';

        try
        {
            $stmt = $this->db->prepare("SELECT 
                    t.url,
                    t.name,
                    COUNT(pt.post_id) AS popularity
                FROM 
                    tags t
                JOIN 
                    post_tag pt ON t.id = pt.tag_id
                JOIN 
                    posts p ON pt.post_id = p.id
                WHERE 
                    p.status = 'published'  -- Только опубликованные посты
                    AND p.article_type = 'post'
                    AND t.name LIKE :tag_name
                GROUP BY 
                    t.url, t.name
                ORDER BY 
                    popularity DESC");
            $stmt->execute([':tag_name' => "%$tagName%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'tagname' => $tagName,
                'success' => true,
                'tagslist' => $results
             ]);
        }
        catch(Exception $e)
        {
            Logger::error('Ошибка при поиске тэгов: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getCsrfToken()
    {
        echo json_encode([
                'success' => true,
                'csrf_token' => CSRF::getToken()
            ]);
        exit;
    }
}