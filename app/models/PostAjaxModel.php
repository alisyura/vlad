<?php

// app/models/PostAjaxModel.php

/**
 * Класс PostAjaxModel содержит методы для работы с данными,
 * связанными с постами и голосами (лайками/дизлайками),
 * которые используются в AJAX-запросах.
 *
 * Класс служит в качестве репозитория (модели) для бизнес-логики,
 * инкапсулируя все операции с базой данных, связанные с голосованием.
 *
 * @property PDO $db Объект подключения к базе данных.
 */
class PostAjaxModel {
    /**
     * @var PDO Объект подключения к базе данных.
     */
    private $db;
    
    /**
     * Конструктор класса PostAjaxModel.
     *
     * @param PDO $pdo Объект PDO, внедряемый через Service Container.
     */
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Находит ID посетителя по его UUID.
     *
     * @param string $uuid Уникальный идентификатор посетителя.
     * @return int|null ID посетителя или null, если посетитель не найден.
     */
    public function getVisitorIdForUUID(string $uuid): ?int
    {
        $stmtVisitor = $this->db->prepare("SELECT id FROM visitors WHERE uuid = :uuid");
        $stmtVisitor->execute([':uuid' => $uuid]);
        $visitor = $stmtVisitor->fetch(PDO::FETCH_ASSOC);

        return $visitor ? (int)$visitor['id'] : null;
    }

    /**
     * Находит существующий ID посетителя по его UUID или создает нового.
     *
     * Используется транзакция с блокировкой для избежания состояния гонки
     * при одновременном запросе от нового посетителя.
     *
     * @param string $uuid Уникальный идентификатор посетителя.
     * @return int ID посетителя в базе данных.
     * @throws PDOException Если возникает ошибка при выполнении запроса к БД.
     */
    public function getOrCreateVisitorId(string $uuid): int {
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
     * Проверяет, голосовал ли посетитель уже за определенный пост.
     *
     * @param int $visitorId ID посетителя.
     * @param string $postUrl URL-адрес поста.
     * @return bool Возвращает true, если посетитель уже голосовал, иначе false.
     */
    public function checkIfVisitorHasAlreadyVoted(int $visitorId, string $postUrl): bool
    {
        Logger::debug("checkIfVisitorHasAlreadyVoted. голосовал ли visitor={$visitorId} за пост");

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
        $hasVoted = $stmt->fetchColumn() !== false;
        Logger::debug("checkIfVisitorHasAlreadyVoted. Результат={$hasVoted}");

        return $hasVoted;
    }

    /**
     * Находит ID поста по его URL.
     *
     * @param string $postUrl URL-адрес поста.
     * @return int|null ID поста или null, если пост не найден.
     */
    public function findPostByUrl(string $postUrl) : int|null
    {
        // Получаем post_id по его Url
        $stmt = $this->db->prepare("SELECT id FROM posts WHERE url = :post_url");
        $stmt->execute([':post_url' => $postUrl]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            return null;
        }
        $postId = $post['id'];
        Logger::debug("reaction. post_id=".$postId);

        return $postId;
    }

    /**
     * Добавляет новый голос за пост.
     *
     * @param int $postId ID поста.
     * @param int $visitorId ID посетителя.
     * @param string $voteType Тип голоса (например, 'like' или 'dislike').
     * @return void
     */
    public function addNewVote(int $postId, int $visitorId, string $voteType): void
    {
        // Шаг 3: Добавляем новый голос
        Logger::debug("addNewVote. Добавляем новый голос. ".
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
    }

    /**
     * Получает текущее количество лайков и дизлайков для поста.
     *
     * @param int $postId ID поста.
     * @return array Ассоциативный массив с ключами 'likes_count' и 'dislikes_count'.
     */
    public function getPostLikeDislikeCounters(int $postId): array
    {
        Logger::debug("getPostLikeDislikeCounters. Возвращаем обновлённые счетчики. ".':post_id='.$postId);
        $stmt = $this->db->prepare("
            SELECT likes_count, dislikes_count FROM posts WHERE id = :post_id
        ");
        $stmt->execute([':post_id' => $postId]);

        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Возвращаем пустой массив, если пост не найден
        return $counts ? $counts : ['likes_count' => 0, 'dislikes_count' => 0];
    }

    /**
     * Находит информацию о нескольких постах по их URL-адресам.
     *
     * Этот метод получает данные о посте, а также тип голоса, если
     * указанный посетитель уже голосовал за этот пост. Использует LEFT JOIN.
     *
     * @param string[] $postUrls Массив URL-адресов постов.
     * @param int $visitorId ID посетителя.
     * @return array Массив ассоциативных массивов с данными постов.
     * Возвращает пустой массив, если ни один пост не найден.
     */
    public function findPostsByUrls(array $postUrls, int $visitorId): array
    {
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

        // Собираем параметры для выполнения: сначала visitorId, затем URL-адреса
        $params = array_merge([$visitorId], $postUrls); // visitor_id первым

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     * Возвращает ID первого найденного пользователя с указанной ролью.
     *
     * @param string $adminRoleName Имя роли для поиска (например, 'admin').
     * @return int|null ID пользователя или null, если пользователь с такой ролью не найден.
     */
    public function getAdminId(string $adminRoleName): ?int
    {
        $stmt = $this->db->prepare("
            SELECT u.id 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = :admin_role_name
                AND u.built_in = 1
            ORDER BY u.id ASC
            LIMIT 1");
        $stmt->execute([':admin_role_name' => $adminRoleName]);
        
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }
}