<?php
// app/models/AdminPostsModel.php

class AdminPostsModel extends BaseModel {
    /**
     * Получает пост по ID с категориями и тегами.
     * @param int $id ID поста.
     * @param string $articleType Тип поста (post/page).
     * @return array|null Данные поста или null, если пост не найден.
     */
    public function getPostById(int $id, string $articleType): ?array
    {
        $sql = "SELECT 
                    p.id,
                    p.url,
                    p.title,
                    p.content,
                    p.excerpt,
                    p.meta_title,
                    p.meta_keywords,
                    p.meta_description,
                    p.status,
                    p.article_type,
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
                WHERE p.id = :id AND p.article_type = :article_type
                GROUP BY p.id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':article_type', $articleType, PDO::PARAM_STR);
            $stmt->execute();
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                return null; // Возвращаем null, если запись не найдена
            }

            $post['selected_categories'] = !empty($post['category_ids']) ? explode(',', $post['category_ids']) : [];
            $post['selected_tags'] = !empty($post['tag_names']) ? explode(';;', $post['tag_names']) : [];
            
            unset($post['category_ids'], $post['category_names'], $post['tag_ids'], $post['tag_names']);
            
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
                $thumbnailMediaId = (new AdminMediaModel())->getMediaIdByUrl($postData['thumbnail_url']);
            }

            // SQL-запрос для обновления поста
            $sql = "UPDATE posts SET
                        status = :status,
                        title = :title,
                        content = :content,
                        excerpt = :excerpt,
                        meta_title = :meta_title,
                        meta_description = :meta_description,
                        meta_keywords = :meta_keywords,
                        thumbnail_media_id = :thumbnail_media_id,
                        updated_at = :updated_at
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $postId,
                ':status' => $postData['status'],
                ':title' => $postData['title'],
                ':content' => $postData['content'],
                ':excerpt' => $postData['excerpt'],
                ':meta_title' => $postData['meta_title'],
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
            throw $e;
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
                $thumbnailMediaId = (new AdminMediaModel())->getMediaIdByUrl($postData['thumbnail_url']);
            }
            
            // Исправленный SQL-запрос с учетом правильных названий столбцов
            $sql = "INSERT INTO posts (
                        user_id, article_type, status, title, content, url, 
                        excerpt, meta_title, meta_description, meta_keywords, 
                        thumbnail_media_id, created_at, updated_at)
                    VALUES (
                        :user_id, :article_type, :status, :title, :content, :url, 
                        :excerpt, :meta_title, :meta_description, :meta_keywords, 
                        :thumbnail_media_id, :created_at, :updated_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $postData['user_id'],
                ':article_type' => $postData['article_type'],
                ':status' => $postData['status'],
                ':title' => $postData['title'],
                ':content' => $postData['content'],
                ':url' => $postData['url'],
                ':excerpt' => $postData['excerpt'], // Исправлено на excerpt
                ':meta_title' => $postData['meta_title'],
                ':meta_description' => $postData['meta_description'],
                ':meta_keywords' => $postData['meta_keywords'],
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
     * Проверяет существование поста по URL, ID или по обоим.
     *
     * @param int|null $postId ID поста.
     * @param string|null $url URL поста.
     * @return bool True, если пост существует, false в противном случае.
     */
    public function postExists(?int $postId = null, ?string $url = null): bool
    {
        // Если ни URL, ни ID не переданы, пост не может существовать.
        if (is_null($url) && is_null($postId)) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM posts WHERE 1";
        $params = [];

        // Добавляем условие для URL, если оно передано.
        if (!is_null($url)) {
            $sql .= " AND url = :url";
            $params[':url'] = $url;
        }

        // Добавляем условие для ID, если оно передано.
        if (!is_null($postId)) {
            $sql .= " AND id = :id";
            $params[':id'] = $postId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Возвращаем true, если количество записей > 0
        return $stmt->fetchColumn() > 0;
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
        Logger::debug("linkPostToTags. Начало. postId=$postId, tagsString=$tagsString");

        // 1. Очистка и нормализация тегов
        $tagsArray = array_map('trim', explode(',', $tagsString));
        $tagsArray = array_filter($tagsArray);
        if (empty($tagsArray)) {
            Logger::debug("linkPostToTags. Теги отсутствуют. Конец.");
            return true;
        }
        
        // Создаём уникальную карту: имя тега в нижнем регистре => оригинальное имя
        $tagNameToOriginalMap = [];
        foreach ($tagsArray as $originalTagName) {
            $lowerCaseTagName = mb_strtolower($originalTagName);
            if (!isset($tagNameToOriginalMap[$lowerCaseTagName])) {
                $tagNameToOriginalMap[$lowerCaseTagName] = $originalTagName;
            }
        }
        
        $tagsNamesToProcess = array_keys($tagNameToOriginalMap);
        $placeholders = implode(',', array_fill(0, count($tagsNamesToProcess), '?'));
        
        Logger::debug("linkPostToTags. Теги для обработки: " . 
            print_r($tagsNamesToProcess, true));

        // 2. Поиск существующих тегов в БД по имени
        $sqlSelect = "SELECT LOWER(name) AS name_lower, id 
                        FROM tags 
                        WHERE name IN ($placeholders)";
        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->execute(array_values($tagsNamesToProcess));
        $existingTags = $stmtSelect->fetchAll(PDO::FETCH_KEY_PAIR); // Получаем массив [name_lower => ID]
        
        Logger::debug("linkPostToTags. Существующие теги из БД (имя => ID): " . 
            print_r($existingTags, true));
        
        // 3. Определяем, какие теги нужно создать
        $newTagNames = [];
        foreach ($tagsNamesToProcess as $tagName) {
            if (!array_key_exists($tagName, $existingTags)) {
                $newTagNames[] = $tagName;
            }
        }
        
        Logger::debug("linkPostToTags. Новые теги для вставки: " . 
            print_r($newTagNames, true));

        // 4. Массовая вставка новых тегов
        if (!empty($newTagNames)) {
            $values = [];
            $params = [];
            foreach ($newTagNames as $name) {
                $originalName = $tagNameToOriginalMap[$name];
                $url = transliterate($originalName);
                $values[] = "(?, ?)";
                $params[] = $originalName;
                $params[] = $url;
            }
            
            $sqlInsert = "INSERT IGNORE INTO tags (name, url) 
                            VALUES " . implode(',', $values);
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute($params);
            
            $rowsInserted = $stmtInsert->rowCount();
            Logger::debug("linkPostToTags. Вставлено новых тегов: $rowsInserted");
        }
        
        // 5. Поиск ID всех тегов (включая только что созданные)
        $allTagIds = [];
        if (!empty($tagsNamesToProcess)) {
            $sqlSelectAll = "SELECT id FROM tags WHERE name IN ($placeholders)";
            $stmtSelectAll = $this->db->prepare($sqlSelectAll);
            $stmtSelectAll->execute($tagsNamesToProcess);
            $allTagIds = $stmtSelectAll->fetchAll(PDO::FETCH_COLUMN);
            Logger::debug("linkPostToTags. ID всех тегов: " . print_r($allTagIds, true));
        }
        
        // 6. Массовая вставка связей между постом и тегами
        if (!empty($allTagIds)) {
            $values = [];
            $params = [];
            foreach ($allTagIds as $tagId) {
                $values[] = "(?, ?)";
                $params[] = $postId;
                $params[] = $tagId;
            }
            $sqlLink = "INSERT IGNORE INTO post_tag (post_id, tag_id) 
                        VALUES " . implode(',', $values);
            $stmtLink = $this->db->prepare($sqlLink);
            $stmtLink->execute($params);
            $rowsInserted = $stmtLink->rowCount();
            Logger::debug("linkPostToTags. Привязано тегов к посту $postId: $rowsInserted");
        }

        Logger::debug("linkPostToTags. Конец. Выполнено");

        return true;
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
