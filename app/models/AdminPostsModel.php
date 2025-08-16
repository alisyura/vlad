<?php
// app/models/AdminPostsModel.php

class AdminPostsModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Получает общее количество постов. Необходимо для пагинации.
     *
     * @param string $article_type Тип статьи. post или page
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount(string $article_type) {
        $sql = "SELECT COUNT(id) AS total_posts 
                FROM posts 
                WHERE article_type = :article_type AND status <> 'deleted'";
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
                     AND status <> 'deleted'
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

    /**
     * Получает пост по ID с категориями и тегами.
     * @param string $url URL поста.
     * @return array|null Данные поста или null, если пост не найден.
     */
    public function getPostByUrl(int $url): ?array
    {
        $sql = "SELECT 
                    p.*, 
                    u.name AS author_name,
                    GROUP_CONCAT(DISTINCT c.id ORDER BY c.name SEPARATOR ',') AS category_ids,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ';;') AS category_names,
                    GROUP_CONCAT(DISTINCT t.id ORDER BY t.name SEPARATOR ',') AS tag_ids,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ';;') AS tag_names,
                    m.file_path as thumbnail_url
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_category pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                LEFT JOIN post_tag pt ON p.id = pt.post_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                LEFT JOIN media m ON p.thumbnail_media_id = m.id
                WHERE p.url = :url AND p.article_type = 'post'
                GROUP BY p.id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':url', $url, PDO::PARAM_STR);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                $post['selected_categories'] = !empty($post['category_ids']) ? explode(',', $post['category_ids']) : [];
                $post['selected_tags'] = !empty($post['tag_names']) ? explode(';;', $post['tag_names']) : [];
                // Убираем промежуточные поля
                unset($post['category_ids'], $post['category_names'], $post['tag_ids'], $post['tag_names']);
            }
            return $post;
        } catch (PDOException $e) {
            Logger::error("Error fetching post by ID with categories and tags: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получает пост по ID с категориями и тегами.
     * @param int $id ID поста.
     * @return array|null Данные поста или null, если пост не найден.
     */
    public function getPostById(int $id): ?array
    {
        $sql = "SELECT 
                    p.*, 
                    u.name AS author_name,
                    GROUP_CONCAT(DISTINCT c.id ORDER BY c.name SEPARATOR ',') AS category_ids,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ';;') AS category_names,
                    GROUP_CONCAT(DISTINCT t.id ORDER BY t.name SEPARATOR ',') AS tag_ids,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ';;') AS tag_names,
                    m.file_path as thumbnail_url
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_category pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                LEFT JOIN post_tag pt ON p.id = pt.post_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                LEFT JOIN media m ON p.thumbnail_media_id = m.id
                WHERE p.id = :id AND p.article_type = 'post'
                GROUP BY p.id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                $post['selected_categories'] = !empty($post['category_ids']) ? explode(',', $post['category_ids']) : [];
                $post['selected_tags'] = !empty($post['tag_names']) ? explode(';;', $post['tag_names']) : [];
                // Убираем промежуточные поля
                unset($post['category_ids'], $post['category_names'], $post['tag_ids'], $post['tag_names']);
            }
            return $post;
        } catch (PDOException $e) {
            Logger::error("Error fetching post by ID with categories and tags: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Обновляет существующий пост.
     * @param int $postId ID поста.
     * @param array $postData Данные поста.
     * @param array $categories Массив ID категорий.
     * @param string $tagsString Строка тегов, разделённая запятыми.
     * @return bool Успех операции.
     */
    public function updatePost(int $postId, array $postData, array $categories = [], string $tagsString = ''): bool
    {
        try {
            $this->db->beginTransaction();

            $thumbnailMediaId = null;
            if (!empty($postData['thumbnail_url'])) {
                $thumbnailMediaId = $this->getMediaIdByUrl($postData['thumbnail_url']);
            }

            // SQL-запрос для обновления поста
            $sql = "UPDATE posts SET
                        status = :status,
                        title = :title,
                        content = :content,
                        url = :url,
                        excerpt = :excerpt,
                        description = :meta_description,
                        keywords = :meta_keywords,
                        thumbnail_media_id = :thumbnail_media_id,
                        updated_at = :updated_at
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $postId,
                ':status' => $postData['status'],
                ':title' => $postData['title'],
                ':content' => $postData['content'],
                ':url' => $postData['url'],
                ':excerpt' => $postData['excerpt'],
                ':meta_description' => $postData['meta_description'],
                ':meta_keywords' => $postData['meta_keywords'],
                ':thumbnail_media_id' => $thumbnailMediaId,
                ':updated_at' => date('Y-m-d H:i:s')
            ]);

            // Удаляем старые связи перед добавлением новых
            $this->deletePostLinks($postId);

            // Добавляем новые связи с категориями и тегами
            if (!empty($categories)) {
                $this->linkPostToCategories($postId, $categories);
            }

            if (!empty($tagsString)) {
                $this->linkPostToTags($postId, $tagsString);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Error updating post with ID $postId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удаляет все связи поста с категориями и тегами.
     * @param int $postId ID поста.
     * @return bool Успех операции.
     */
    private function deletePostLinks(int $postId): bool
    {
        try {
            $sqlCategories = "DELETE FROM post_category WHERE post_id = :post_id";
            $stmtCategories = $this->db->prepare($sqlCategories);
            $stmtCategories->execute([':post_id' => $postId]);

            $sqlTags = "DELETE FROM post_tag WHERE post_id = :post_id";
            $stmtTags = $this->db->prepare($sqlTags);
            $stmtTags->execute([':post_id' => $postId]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error("Error deleting post links for post ID $postId: " . $e->getMessage());
            return false;
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
    public function linkPostToTags(int $postId, string $tagsString): bool
    {
        Logger::debug("linkPostToTags. postId=$postId, tagsString=$tagsString");

        $tagsArray = array_map('trim', explode(',', $tagsString));
        $tagsArray = array_filter($tagsArray);
        if (empty($tagsArray)) {
            return true;
        }

        Logger::debug("linkPostToTags. tagsArray ".print_r($tagsArray, true));

        // Нормализуем имена: убираем дубли, приводим к нижнему регистру для сравнения
        $uniqueTags = array_unique(array_map('mb_strtolower', $tagsArray));
        Logger::debug("linkPostToTags. uniqueTags. ".print_r($uniqueTags, true));
        $tagIds = [];

        $sqlInsert = "INSERT IGNORE INTO tags (name, url) VALUES (:name, :url)";
        $stmtInsert = $this->db->prepare($sqlInsert);

        $sqlSelect = "SELECT id FROM tags WHERE LOWER(name) = :name LIMIT 1";
        $stmtSelect = $this->db->prepare($sqlSelect);

        foreach ($uniqueTags as $tagName) {
            Logger::debug("linkPostToTags. process tagName=".$tagName);

            // Пробуем найти существующий тег по имени (с учётом регистра)
            $stmtSelect->execute([':name' => mb_strtolower($tagName)]);
            $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Тег уже есть
                $tagId = (int)$result['id'];
                Logger::debug("linkPostToTags. tagName=$tagName уже существует. Id=$tagId ");
            } else {
                // Создаём новый тег
                $tagUrl = transliterate($tagName);
                // Убедимся, что URL уникален
                $existingByUrl = $this->db->prepare("SELECT id FROM tags WHERE url = :url LIMIT 1");
                $existingByUrl->execute([':url' => $tagUrl]);
                if ($existingByUrl->fetch()) {
                    // Если URL занят другим тегом — пропускаем или генерируем новый
                    continue; // или $tagUrl .= '-2' и т.д.
                }
                $stmtInsert->execute([
                    ':name' => $tagName,
                    ':url' => $tagUrl
                ]);
                $tagId = (int)$this->db->lastInsertId();
            }

            if ($tagId) {
                $tagIds[] = $tagId;
            }
        }

        // Связываем пост с найденными/созданными тегами
        $sqlLink = "INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:post_id, :tag_id)";
        $stmtLink = $this->db->prepare($sqlLink);
        foreach ($tagIds as $tagId) {
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

    /**
     * Помечает пост/страницу как удалённый (soft delete), устанавливая статус 'deleted'.
     * 
     * @param int $postId ID поста.
     * @return bool true в случае успеха, false — если произошла ошибка или пост не найден.
     */
    public function setPostAsDeleted(int $postId): bool
    {
        $sql = "UPDATE posts SET status = 'deleted', updated_at = :updated_at WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $postId,
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Возвращаем true, только если хотя бы одна строка была обновлена
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error("Ошибка при пометке поста ID $postId как удалённого: " . $e->getMessage());
            return false;
        }
    }
}
