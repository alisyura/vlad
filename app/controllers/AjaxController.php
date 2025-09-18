<?php
//declare(strict_types=1);
/**
 * Класс AjaxController отвечает за обработку AJAX-запросов,
 * связанных с взаимодействием пользователей с контентом,
 * таким как голосование за посты.
 *
 * @property Request $request Объект HTTP-запроса.
 * @property PostAjaxModel $model Модель для работы с данными постов.
 * @property ReactionService $reactionService Сервис для обработки логики голосования.
 */
class AjaxController
{
    use JsonResponseTrait;

    private $db;
    private $request;
    private PostAjaxModel $model;
    private ReactionService $reactionService;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param ReactionService $reactionService Сервис для голосования, внедряется через DI-контейнер.
     * @param PostAjaxModel $model Модель для работы с постами, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, ReactionService $reactionService, 
        PostAjaxModel $model)
    {
        header('Content-Type: application/json');

        // $this->db = Database::getConnection();
        $this->request = $request;
        $this->model = $model;
        $this->reactionService = $reactionService;
    }

    public function getPostVotes()
    {
        $posts = $this->request->json('posts') ?? '';
        if (empty($posts)) {
            $this->sendErrorJsonResponse('Нет постов', 404);
            exit;
        }

        try {

            $uuid = getVisitorCookie();

            $visitorId=$this->model->getVisitorIdForUUID($uuid);

            // Убираем дубликаты и пустые значения
            $postUrls = array_unique(array_filter($posts));

            if (empty($postUrls)) {
                $this->sendErrorJsonResponse('Нет корректных постов', 422);
                exit;
            }

            $results = $this->model->findPostsByUrls($postUrls, $visitorId);

            $this->sendSuccessJsonResponse('Голоса получены', 200, ['votes' => $results]);

        } catch (Throwable $e) {
            Logger::error('getPostVotes. Ошибка получения голосов постов. '.$e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка получения голосов постов.', 500);
            exit();
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
     * Обрабатывает голосование за пост через AJAX-запрос.
     *
     * Метод принимает данные из JSON-запроса, вызывает сервис для обработки
     * бизнес-логики и отправляет JSON-ответ об успехе или ошибке.
     *
     * @return void
     */
    public function reaction(): void
    {
        $postUrl = $this->request->json('postUrl') ?? '';
        $voteType = $this->request->json('type') ?? '';

        Logger::debug("reaction. postUrl=".$postUrl.", voteType=".$voteType);
        $uuid = getVisitorCookie();
        Logger::debug("reaction. uuid=".$uuid);

        try {
            $result = $this->reactionService->handleVote($postUrl, $voteType, $uuid);

            $resJson = [
                'likes' => $result['likes_count'],
                'dislikes' => $result['dislikes_count']
            ];
            Logger::debug("reaction. resJson=".json_encode($resJson));
            $this->sendSuccessJsonResponse('Спасибо за ваш голос', 200, $resJson);
        } catch (ReactionException $e) {
            $errorJson = json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'uuid' => $uuid,
                'message' => $e->getMessage()
            ]);

            Logger::error("reaction. {$e->getMessage()}. res={$errorJson}", $e->getTraceAsString());

            $this->sendErrorJsonResponse($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            $errorJson = json_encode([
                'success' => false,
                'postUrl' => $postUrl,
                'type' => $voteType,
                'cookie' => $uuid,
                'uuid' => $uuid,
                'message' => $e->getMessage()
            ]);

            Logger::error("reaction. Ошибка при голосовании. res={$errorJson}", $e->getTraceAsString());

            $this->sendErrorJsonResponse('Ошибка при регистрации голоса', 500);
        }
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

    public function sendMsg()
    {
        try 
        {
            // 1. Get and Validate Data (using your Validator class)
            $data = [
                'name' => trim($this->request->post('name') ?? ''),
                'email' => trim($this->request->post('email') ?? ''),
                'title' => trim($this->request->post('title') ?? ''),
                'text' => trim($this->request->post('text') ?? '')
            ];
            $file = $this->request->file('image') ?? null;

            $validator = new ContactFormValidator();
            $errors = $validator->validate($data, $file);

            if (count($errors) > 0) {
                $this->sendErrorJsonResponse($errors);
                return;
            }

            // 2. Pass to the Mailer Service
            $mailer = new ContactMailerService();
            $result = $mailer->sendContactEmail($data, $file);

            if ($result['success']) {
                $this->sendSuccessJsonResponse('Ваше сообщение успешно отправлено');
            } else {
                $this->sendErrorJsonResponse('Ошибка при отправке сообщения');
            }
        } catch(Exception $e) {
            Logger::error("sendMeg. Ошибка при отправке сообщения.", [$e->getTraceAsString()]);
                
            $this->sendErrorJsonResponse('При отправке сообщения произошла ошибка');
        }
    }

    public function searchTags()
    {
        // $tagName = trim($_POST['name']) ?? '';

        $data = json_decode(file_get_contents('php://input'), true);
        $tagName = $data['name'] ?? '';

        $tagName = $this->request->json('name');
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
        $this->sendSuccessJsonResponse('', 200, ['csrf_token' => CSRF::getToken()]);
        // echo json_encode([
        //         'success' => true,
        //         'csrf_token' => CSRF::getToken()
        //     ]);
        exit;
    }
}