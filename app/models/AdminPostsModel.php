<?php
// app/models/AdminPostsModel.php

class AdminPostsModel {
    private $db;

    public function __construct() {
        $dbHost = Config::getDbHost('DB_HOST');
        $dbName = Config::getDbHost('DB_NAME');
        $dbUser = Config::getDbHost('DB_USER');
        $dbPass = Config::getDbHost('DB_PASS');

        try {
            $this->db = new PDO(
                'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            Logger::error("Database connection error: " . $e->getTraceAsString());
            die("Произошла ошибка при подключении к базе данных. Пожалуйста, попробуйте позже.");
        }
    }

    /**
     * Получает общее количество постов. Необходимо для пагинации.
     *
     * @param string $article_type Тип статьи. post или page
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount(string $article_type) {
        $sql = "SELECT COUNT(id) AS total_posts FROM posts WHERE article_type = :article_type";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':article_type' => $article_type]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total_posts'];
        } catch (PDOException $e) {
            Logger::error("Error fetching total posts count: " . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * Получает список постов для админ-панели с пагинацией.
     * Включает данные об авторе, связанных категориях и тегах.
     *
     * @param string $article_type Тип статьи. post или page
     * @param int $limit Количество постов на страницу.
     * @param int $offset Смещение (сколько постов пропустить).
     * @param string $sortBy Колонка для сортировки (например, 'title', 'created_at').
     * @param string $sortOrder Направление сортировки ('ASC' или 'DESC').
     * @return array Список постов, каждый из которых содержит массив категорий и массив тегов.
     */
    public function getPosts(string $article_type, int $limit, int $offset,
                             string $sortBy = 'created_at', string $sortOrder = 'DESC') {
        // Допустимые поля для сортировки и их соответствие в базе данных
        $allowedSortColumns = [
            'id' => 'p.id',
            'title' => 'p.title',
            'author' => 'u.name',
            'categories' => 'category_names_concat', // Сортируем по конкатенированному списку категорий
            'tags' => 'tag_names_concat', // Сортируем по конкатенированному списку тегов
            'status' => 'p.status',
            'created_at' => 'p.created_at',
            'updated_at' => 'p.updated_at'
        ];

        // Проверяем, что $sortBy является допустимой колонкой
        $orderByColumn = $allowedSortColumns['created_at']; // По умолчанию
        if (array_key_exists($sortBy, $allowedSortColumns)) {
            $orderByColumn = $allowedSortColumns[$sortBy];
        }

        // Проверяем, что $sortOrder является допустимым направлением
        $sortOrder = strtoupper($sortOrder); // Приводим к верхнему регистру
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC'; // По умолчанию DESC
        }

        $sql = "SELECT
                     p.id,
                     p.title,
                     p.url,
                     p.status,
                     p.created_at,
                     p.updated_at,
                     u.name AS author_name,
                     GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS category_names_concat,
                     GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tag_names_concat,
                     GROUP_CONCAT(DISTINCT c.name, '||', c.url ORDER BY c.name SEPARATOR ';;') AS category_data,
                     GROUP_CONCAT(DISTINCT t.name, '||', t.url ORDER BY t.name SEPARATOR ';;') AS tag_data
                 FROM
                     posts p
                 JOIN
                     users u ON p.user_id = u.id
                 LEFT JOIN
                     post_category pc ON p.id = pc.post_id
                 LEFT JOIN
                     categories c ON pc.category_id = c.id
                 LEFT JOIN
                     post_tag pt ON p.id = pt.post_id
                 LEFT JOIN
                     tags t ON pt.tag_id = t.id
                 WHERE
                     article_type = :article_type
                 GROUP BY
                     p.id
                 ORDER BY
                     {$orderByColumn} {$sortOrder}
                 LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':article_type', $article_type, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rawPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $posts = [];
            foreach ($rawPosts as $post) {
                // Разбираем данные категорий
                $post['categories'] = [];
                if (!empty($post['category_data'])) {
                    $category_pairs = explode(';;', $post['category_data']);
                    foreach ($category_pairs as $pair) {
                        if (strpos($pair, '||') !== false) {
                            list($name, $url) = explode('||', $pair, 2);
                            $post['categories'][] = ['name' => $name, 'url' => $url];
                        }
                    }
                }
                unset($post['category_data']); // Удаляем сырые данные после обработки
                
                // Теперь используем category_names_concat для вывода в HTML, 
                // если вы хотите именно строку, а не массив объектов.
                // Или можете продолжать использовать $post['categories'] для построения ссылок.
                // В вашем контроллере вы уже делаете это, так что category_names_concat не строго нужен для вывода,
                // но помогает в сортировке.
                $post['category_names'] = $post['category_names_concat']; // Сохраняем конкатенированное имя для сортировки/отображения
                unset($post['category_names_concat']); // Удаляем сырые данные после обработки

                // Разбираем данные тегов
                $post['tags'] = [];
                if (!empty($post['tag_data'])) {
                    $tag_pairs = explode(';;', $post['tag_data']);
                    foreach ($tag_pairs as $pair) {
                         if (strpos($pair, '||') !== false) {
                             list($name, $url) = explode('||', $pair, 2);
                             $post['tags'][] = ['name' => $name, 'url' => $url];
                         }
                    }
                }
                unset($post['tag_data']); // Удаляем сырые данные после обработки

                $post['tag_names'] = $post['tag_names_concat']; // Сохраняем конкатенированное имя
                unset($post['tag_names_concat']); // Удаляем сырые данные после обработки

                $posts[] = $post;
            }

            return $posts;

        } catch (PDOException $e) {
            Logger::error("Error fetching paginated posts in AdminPostsModel: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getPostById(int $id) {
        $sql = "SELECT p.*, u.name AS author_name
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :id AND p.article_type = 'post'"; // Убедитесь, что это именно пост
    
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($post) {
                // Здесь вы можете также получить категории и теги, если они уже есть в БД
                // (логика похожа на getPosts, но для одного поста)
                $post['categories'] = []; // Placeholder
                $post['tags'] = []; // Placeholder
            }
            return $post;
    
        } catch (PDOException $e) {
            Logger::error("Error fetching post by ID: " . $e->getTraceAsString());
            return null;
        }
    }
    
    // --- НОВЫЕ МЕТОДЫ ДЛЯ СОЗДАНИЯ ПОСТА ---

    /**
     * Получает все категории из базы данных.
     * @return array Список категорий.
     */
    public function getAllCategories(): array {
        $sql = "SELECT id, name FROM categories WHERE url <> 'tegi' ORDER BY name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching all categories: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Получает все теги из базы данных.
     * @return array Список тегов.
     */
    public function getAllTags(): array {
        $sql = "SELECT id, name, url FROM tags ORDER BY name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching all tags: " . $e->getTraceAsString());
            return [];
        }
    }
    

    /**
     * Вставляет новый пост и связывает его с категориями и тегами.
     * @param array $postData Данные поста.
     * @param array $categories Массив ID категорий.
     * @param string $tagsString Строка тегов, разделённая запятыми.
     * @return int|false ID нового поста или false в случае ошибки.
     */
    public function createPost(array $postData, array $categories = [], string $tagsString = '')
    {
        try {
            $this->db->beginTransaction();

            // Получаем ID медиафайла по его URL
            $thumbnailMediaId = null;
            if (!empty($postData['thumbnail_url'])) {
                $thumbnailMediaId = $this->getMediaIdByUrl($postData['thumbnail_url']);
            }
            
            // Исправленный SQL-запрос с учетом правильных названий столбцов
            $sql = "INSERT INTO posts (
                        user_id, article_type, status, title, content, url, 
                        excerpt, description, keywords, thumbnail_media_id, 
                        created_at, updated_at)
                    VALUES (
                        :user_id, :article_type, :status, :title, :content, :url, 
                        :excerpt, :description, :keywords, :thumbnail_media_id, 
                        :created_at, :updated_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $postData['user_id'],
                ':article_type' => $postData['article_type'],
                ':status' => $postData['status'],
                ':title' => $postData['title'],
                ':content' => $postData['content'],
                ':url' => $postData['url'],
                ':excerpt' => $postData['excerpt'], // Исправлено на excerpt
                ':description' => $postData['meta_description'],
                ':keywords' => $postData['meta_keywords'],
                ':thumbnail_media_id' => $thumbnailMediaId,
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            $postId = $this->db->lastInsertId();

            if ($postId && !empty($categories)) {
                $this->linkPostToCategories($postId, $categories);
            }

            if ($postId && !empty($tagsString)) {
                $this->linkPostToTags($postId, $tagsString);
            }

            $this->db->commit();
            return $postId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Error creating post: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Получает ID медиафайла по его URL (file_path).
     * @param string $fileUrl URL файла.
     * @return int|null ID файла или null, если не найден.
     */
    private function getMediaIdByUrl(string $fileUrl): ?int
    {
        if (empty($fileUrl)) {
            return null;
        }
        $sql = "SELECT id FROM media WHERE file_path = :file_path LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':file_path' => $fileUrl]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['id'] : null;
    }
    
    /**
     * Проверяет уникальность URL.
     * @param string $url URL для проверки.
     * @return bool True, если URL уникален, false в противном случае.
     */
    public function isUrlUnique(string $url): bool {
        $sql = "SELECT COUNT(*) FROM posts WHERE url = :url";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':url' => $url]);
        return $stmt->fetchColumn() === 0;
    }

    /**
     * Связывает пост с категориями.
     * @param int $postId ID поста.
     * @param array $categoryIds Массив ID категорий.
     * @return bool Успех операции.
     */
    public function linkPostToCategories(int $postId, array $categoryIds): bool {
        $sql = "INSERT IGNORE INTO post_category (post_id, category_id) VALUES (:post_id, :category_id)";
        $stmt = $this->db->prepare($sql);
        foreach ($categoryIds as $categoryId) {
            $stmt->execute([':post_id' => $postId, ':category_id' => $categoryId]);
        }
        return true;
    }

    /**
     * Связывает пост с тегами. Если тег не существует, он создается.
     * @param int $postId ID поста.
     * @param string $tagsString Строка тегов, разделённая запятыми.
     * @return bool Успех операции.
     */
    public function linkPostToTags(int $postId, string $tagsString): bool {
        $tagsArray = array_map('trim', explode(',', $tagsString));
        $tagsArray = array_filter($tagsArray);
        
        if (empty($tagsArray)) {
            return true;
        }

        // Вставляем теги, которые еще не существуют
        $sqlInsertTags = "INSERT IGNORE INTO tags (name, url) VALUES (:name, :url)";
        $stmtInsertTags = $this->db->prepare($sqlInsertTags);
        foreach ($tagsArray as $tagName) {
            $tagUrl = transliterate($tagName); // Предполагаем, что у вас есть такая функция
            $stmtInsertTags->execute([':name' => $tagName, ':url' => $tagUrl]);
        }

        // Получаем ID всех тегов
        $placeholders = implode(',', array_fill(0, count($tagsArray), '?'));
        $sqlSelectTags = "SELECT id FROM tags WHERE name IN ($placeholders)";
        $stmtSelectTags = $this->db->prepare($sqlSelectTags);
        $stmtSelectTags->execute($tagsArray);
        $tagsIds = $stmtSelectTags->fetchAll(PDO::FETCH_COLUMN);

        // Связываем пост с тегами
        $sqlLink = "INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:post_id, :tag_id)";
        $stmtLink = $this->db->prepare($sqlLink);
        foreach ($tagsIds as $tagId) {
            $stmtLink->execute([':post_id' => $postId, ':tag_id' => $tagId]);
        }
        return true;
    }

    public function searchTagsByName(string $query)
    {
        $sql = "SELECT
                    t.name,
                    t.url,
                    COUNT(pt.post_id) as popularity_count
                FROM
                    tags t
                LEFT JOIN
                    post_tag pt ON t.id = pt.tag_id
                WHERE
                    t.name LIKE :query
                GROUP BY
                    t.id
                ORDER BY
                    popularity_count DESC
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
