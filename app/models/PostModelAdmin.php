<?php
// app/models/PostModelAdmin.php

class PostModelAdmin extends BaseModel {
    /**
     * Определяет статус "удален".
     *
     * @var string
     */
    public const string STATUS_DELETED = 'deleted';
    /**
     * Определяет статус "черновик".
     *
     * @var string
     */
    public const string STATUS_DRAFT = 'draft';
    /**
     * Определяет статус "опубликовано".
     *
     * @var string
     */
    public const string STATUS_PUBLISHED = 'published';
    /**
     * Определяет статус "в ожидании", ожидающий проверки или утверждения.
     *
     * @var string
     */
    public const string STATUS_PENDING = 'pending';

    private AdminMediaModel $mediaModel;

    public function __construct(PDO $db, AdminMediaModel $mediaModel)
    {
        parent::__construct($db);
        $this->mediaModel = $mediaModel;
    }
    
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
            Logger::error("getPostById. Error fetching post by ID with categories and tags.", ['postId' => $id, 'articleType' => $articleType], $e);
            return null;
        }
    }

    /**
     * Обновляет существующий пост.
     * @param int $postId ID поста.
     * @param array $postData Данные поста.
     * @param array $categories Массив ID категорий.
     * @param string $tagsString Строка тегов, разделённая запятыми.
     * @return void
     */
    public function updatePost(int $postId, array $postData, array $categories = [], string $tagsString = ''): void
    {
        try {
            $this->db->beginTransaction();

            $thumbnailMediaId = null;
            if (!empty($postData['thumbnail_url'])) {
                $thumbnailMediaId = $this->mediaModel->getMediaIdByUrl($postData['thumbnail_url']);
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
                    WHERE id = :id AND article_type = :article_type";
            
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
                ':updated_at' => date('Y-m-d H:i:s'),
                ':article_type' => $postData['article_type']
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
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("updatePost. Error updating post with ID: " . $postId, $postData, $e);
            throw $e;
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
            Logger::error("deletePostLinks. Error deleting post links", ['postId' => $postId], $e);
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
                $thumbnailMediaId = $this->mediaModel->getMediaIdByUrl($postData['thumbnail_url']);
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
            $mergedForLog = [...$postData, 'categoriesIds' => $categories];
            $mergedForLog['tagsString'] = $tagsString;
            Logger::error("createPost. Error creating post", $mergedForLog, $e);
            throw $e;
        }
    }

    /**
     * Проверяет существование поста по ID, URL или их комбинации,
     * с возможностью фильтрации по статусу.
     * Статус может быть проверен только при наличии ID или URL.
     *
     * @param int|null $postId ID поста.
     * @param string|null $url URL поста.
     * @param string|null $articleType Тип статьи (post/page).
     * @param string|null $status Статус поста.
     * @return bool True, если пост существует, false в противном случае.
     */
    public function postExists(?int $postId = null, ?string $url = null, 
        ?string $articleType = null, ?string $status = null): bool
    {
        if (is_null($url) && is_null($postId)) {
            return false;
        }

        $whereClauses = [];
        $params = [];

        if (!is_null($postId)) {
            $whereClauses[] = "id = :id";
            $params[':id'] = $postId;
        }

        if (!is_null($url)) {
            $whereClauses[] = "url = :url";
            $params[':url'] = $url;
        }
        
        if (!is_null($status)) {
            $whereClauses[] = "status = :status";
            $params[':status'] = $status;
        }

         if (!is_null($articleType)) {
            $whereClauses[] = "article_type = :article_type";
            $params[':article_type'] = $articleType;
        }

        $sql = "SELECT COUNT(*) FROM posts WHERE " . implode(" AND ", $whereClauses);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
     * @param string $status Статус поста. См. константы в начале класса.
     * @return bool true в случае успеха, false — если произошла ошибка или пост не найден.
     */
    public function setPostStatus(int $postId, string $status, string $articleType): bool
    {
        $sql = "UPDATE posts 
                SET status = :status, updated_at = :updated_at 
                WHERE id = :id AND article_type = :article_type";
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $postId,
                ':status' => $status,
                ':article_type' => $articleType,
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Возвращаем true, только если хотя бы одна строка была обновлена
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error("setPostStatus. Ошибка при установке посту статуса.", ['postId' => $postId, 'status' => $status, 'articleType' => $articleType], $e);
            throw $e;
        }
    }

    /**
     * Выполняет полное удаление поста из базы данных.
     *
     * @param int $postId ID поста.
     * @return bool true в случае успеха, false — если произошла ошибка или пост не найден.
     */
    public function hardDeletePost(int $postId, string $articleType): bool
    {
        // Усиленный запрос: удаляет только если ID совпадает И статус 'trash'
        $sql = "DELETE FROM posts 
                WHERE id = :id 
                AND status = :trash_status 
                AND article_type = :article_type";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $postId,
                ':trash_status' => PostModelAdmin::STATUS_DELETED,
                ':article_type' => $articleType
            ]);

            // Возвращаем true, если удалена хотя бы одна строка
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Логирование и переброс исключения
            Logger::error("hardDeletePost. Ошибка при полном удалении поста: ", [
                'postId' => $postId,
                'articleType' => $articleType
            ], $e);
            throw $e; 
        }
    }

    /**
     * Получает общее количество постов. Необходимо для пагинации.
     *
     * @param string $articleType Тип статьи. post или page
     * @param bool $showThrash Получить список удаленных или активных постов.
     * @param array $filter Список параметров для фильра.
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount(string $articleType, bool $showThrash = false, 
        $filter = []) 
    {
        // Получение условий исключения
        [$excludeCondition, $excludeUrls] = $this->getExcludeConditionAndUrls($articleType);
        
        // --- 1. Инициализация переменных ---
        $conditions = [
            'p.article_type = :article_type'
        ];
        $params = [
            ':article_type' => $articleType
        ];
        $joins = '';
        
        // --- 2. Условие по статусу (стандартное и из фильтра) ---
        if (isset($filter['selectedStatus']) && 
            in_array($filter['selectedStatus'], ['draft', 'pending', 'published', 'deleted'])) {
            
                $conditions[] = "p.status = :status";
            $params[':status'] = $filter['selectedStatus'];
        } else {
            $conditions[] = $showThrash ? "p.status = 'deleted'" : "p.status <> 'deleted'";
        }

        // --- 3. Добавление фильтров из $filterData ---

        // 3.1. Фильтр по категории (selectedCategory)
        if (!empty($filter['selectedCategory'])) {
            $joins .= ' INNER JOIN post_category AS pc ON p.id = pc.post_id';
            $conditions[] = 'pc.category_id = :category_id';
            $params[':category_id'] = (int) $filter['selectedCategory'];
        }
        
        // 3.2. ФИЛЬТР ПО ДАТЕ (selectedPostDate) - С УЧЕТОМ ФОРМАТА d-m-Y
        if (!empty($filter['selectedPostDate'])) {
            $dateInput = $filter['selectedPostDate'];
            
            // Преобразование даты из d-m-Y в Y-m-d
            $dateObject = \DateTime::createFromFormat('d-m-Y', $dateInput);
            
            if ($dateObject !== false) {
                $mysqlDate = $dateObject->format('Y-m-d'); 
                
                $conditions[] = "DATE(p.created_at) = :post_date";
                $params[':post_date'] = $mysqlDate;
            } 
            // Если формат не совпадает, фильтр по дате игнорируется
        }
        
        // 3.3. Фильтр по поисковому запросу (selectedSearchQuery)
        if (!empty($filter['selectedSearchQuery'])) {
            $searchQuery = '%' . $filter['selectedSearchQuery'] . '%';
            $conditions[] = '(p.title LIKE :search_query_title OR p.content LIKE :search_query_content)';
            $params[':search_query_title'] = $searchQuery;
            $params[':search_query_content'] = $searchQuery;
        }

        // --- 4. Условие исключения URL (стандартное) ---
        if (!empty($excludeCondition)) {
            $conditions[] = $excludeCondition;
        }

        // --- 5. Сборка и выполнение запроса ---
        $sql = "SELECT COUNT(p.id) AS total_posts 
                FROM posts AS p
                {$joins} 
                WHERE " . implode(' AND ', $conditions);

        try {
            $stmt = $this->db->prepare($sql);
            
            // Связываем все параметры из $params
            foreach ($params as $key => &$value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindParam($key, $value, $type);
            }

            // Связываем параметры URL (если они есть)
            if (!empty($excludeUrls)) {
                foreach ($excludeUrls as $index => &$url) {
                    // Важно: использование & для корректного bindParam в цикле
                    $stmt->bindParam(":url{$index}", $url, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total_posts'];
            
        } catch (PDOException $e) {
            Logger::error("getTotalPostsCount. Error fetching total posts count", ['sql' => $sql, 'params' => $params], $e);
            throw $e;
        }
    }

    /**
     * Получает список постов для админ-панели с пагинацией.
     * Включает данные об авторе, связанных категориях и тегах.
     *
     * @param string $articleType Тип статьи. post или page
     * @param int $limit Количество постов на страницу.
     * @param int $offset Смещение (сколько постов пропустить).
     * @param string $sortBy Колонка для сортировки (например, 'title', 'created_at').
     * @param string $sortOrder Направление сортировки ('ASC' или 'DESC').
     * @param bool $showThrash Получить список удаленных или активных постов.
     * @param array $filter Список параметров для фильра.
     * @return array Список постов, каждый из которых содержит массив категорий и массив тегов.
     */
    public function getPostsList(string $articleType, int $limit, int $offset,
                             string $sortBy = 'updated_at', string $sortOrder = 'DESC',
                             bool $showThrash = false, $filter = []) 
    {
    
        // Получаем условие исключения и URL-адреса
        [$excludeCondition, $excludeUrls] = $this->getExcludeConditionAndUrls($articleType);

        // ... (Оставшаяся часть кода для allowedSortColumns, проверки $sortBy и $sortOrder) ...
        $allowedSortColumns = [
            'id' => 'p.id',
            'title' => 'p.title',
            'author' => 'u.name',
            'categories' => 'category_names_concat', // Сортируем по конкатенированному списку категорий
            'tags' => 'tag_names_concat', // Сортируем по конкатенированному списку тегов
            'status' => 'p.status',
            'updated_at' => 'p.updated_at'
        ];
        $orderByColumn = array_key_exists($sortBy, $allowedSortColumns) ? $allowedSortColumns[$sortBy] : $allowedSortColumns['updated_at'];
        $sortOrder = (in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) ? strtoupper($sortOrder) : 'DESC';


        // --- 1. Инициализация параметров и условий ---
        $conditions = [
            'p.article_type = :article_type',
        ];
        $params = [
            ':article_type' => $articleType
        ];

        // --- 2. Обработка фильтров (аналогично getTotalPostsCount) ---

        // 2.1. Фильтр по статусу (selectedStatus)
        if (isset($filter['selectedStatus']) && in_array($filter['selectedStatus'], ['draft', 'pending', 'published', 'deleted'])) {
            $conditions[] = "p.status = :status";
            $params[':status'] = $filter['selectedStatus'];
        } else {
            // Стандартная логика: либо мусор, либо всё остальное
            $conditions[] = $showThrash ? "p.status = 'deleted'" : "p.status <> 'deleted'";
        }

        // 2.2. Фильтр по категории (selectedCategory)
        if (!empty($filter['selectedCategory'])) {
            // Поскольку JOIN уже есть, просто добавляем условие WHERE
            $conditions[] = 'pc.category_id = :category_id';
            $params[':category_id'] = (int) $filter['selectedCategory'];
        }
        
        // 2.3. Фильтр по дате (selectedPostDate) - d-m-Y
        if (!empty($filter['selectedPostDate'])) {
            $dateObject = \DateTime::createFromFormat('d-m-Y', $filter['selectedPostDate']);
            if ($dateObject !== false) {
                $mysqlDate = $dateObject->format('Y-m-d'); 
                
                $conditions[] = "DATE(p.created_at) = :post_date";
                $params[':post_date'] = $mysqlDate;
            } 
        }
        
        // 2.4. Фильтр по поисковому запросу (selectedSearchQuery)
        if (!empty($filter['selectedSearchQuery'])) {
            $searchQuery = '%' . $filter['selectedSearchQuery'] . '%';
            // Поиск по заголовку и/или содержимому
            $conditions[] = '(p.title LIKE :search_query_title OR p.content LIKE :search_query_content)';
            $params[':search_query_title'] = $searchQuery;
            $params[':search_query_content'] = $searchQuery;
        }

        // --- 3. Добавляем условие исключения URL (стандартное) ---
        if (!empty($excludeCondition)) {
            $conditions[] = $excludeCondition;
        }

        // --- 4. Собираем запрос ---
        $sql = "SELECT
                        p.id,
                        p.title,
                        p.url,
                        p.status,
                        p.created_at,
                        p.updated_at,
                        p.article_type,
                        u.name AS author_name,
                        -- ... (остальные GROUP_CONCAT выражения) ...
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
                        " . implode(' AND ', $conditions) . "
                    GROUP BY
                        p.id
                    ORDER BY
                        {$orderByColumn} {$sortOrder}
                    LIMIT :limit OFFSET :offset";

        try {
            Logger::debug("getPosts. $articleType, $limit, $offset, $sortBy, $sortOrder. SQL with filters: " . $sql);
            $stmt = $this->db->prepare($sql);
            
            // --- 5. Связываем все параметры ---

            // Связываем лимиты (всегда INT)
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Связываем динамические параметры из $params
            // &$value по ссылке, иначе при вызове execute все параметры 
            // получат последнее значение $value
            foreach ($params as $key => &$value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindParam($key, $value, $type);
            }

            // Связываем параметры URL, если они есть
            if (!empty($excludeUrls)) {
                foreach ($excludeUrls as $index => &$url) {
                    $stmt->bindParam(":url{$index}", $url, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $rawPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->parsePostData($rawPosts);

        } catch (PDOException $e) {
            // ... (логирование ошибки) ...
            Logger::error("getPostsList. Error fetching paginated posts in AdminPostsModel.", 
                [
                    'articleType' => $articleType, 
                    'limit' => $limit,
                    'offset' => $offset,
                    'filter' => $filter, // Добавляем фильтры в лог для отладки
                ], $e);
            throw $e;
        }
    }

    /**
     * Обрабатывает данные, полученные из запроса к базе данных,
     * разделяя объединенные строки категорий и тегов.
     *
     * @param array $rawPosts Массив необработанных данных постов.
     * @return array Массив обработанных данных постов.
     */
    private function parsePostData(array $rawPosts): array
    {
        $posts = [];
        foreach ($rawPosts as $post) {
            $post['categories'] = $this->parseGroupedData($post['category_data']);
            unset($post['category_data']);
            unset($post['category_names_concat']);

            $post['tags'] = $this->parseGroupedData($post['tag_data']);
            unset($post['tag_data']);
            unset($post['tag_names_concat']);

            $posts[] = $post;
        }
        return $posts;
    }

    /**
     * Разделяет строку объединенных данных на массив объектов.
     *
     * @param string|null $data Строка объединенных данных.
     * @return array Массив объектов.
     */
    private function parseGroupedData(?string $data): array
    {
        if (empty($data)) {
            return [];
        }

        $items = [];
        $pairs = explode(';;', $data);
        foreach ($pairs as $pair) {
             if (strpos($pair, '||') !== false) {
                 list($name, $url) = explode('||', $pair, 2);
                 $items[] = ['name' => $name, 'url' => $url];
             }
        }
        return $items;
    }

    /**
     * Получает SQL-условие и URL-адреса для исключения.
     *
     * @param string $article_type Тип статьи ('post' или 'page').
     * @return array Массив, содержащий строку условия и массив URL-адресов.
     */
    private function getExcludeConditionAndUrls(string $article_type): array
    {
        $configKey = null;
        if ($article_type === 'page') {
            $configKey = 'admin.PagesToExclude';
        } elseif ($article_type === 'post') {
            $configKey = 'admin.PostsToExclude';
        }
    
        $excludeUrls = [];
        $excludeCondition = '';
    
        if ($configKey) {
            $rawExcludeUrls = \Config::get($configKey);
            $excludeUrls = array_filter($rawExcludeUrls, 'strlen');
    
            if (!empty($excludeUrls)) {
                $placeholders = array_map(function($index) {
                    return ":url{$index}";
                }, array_keys($excludeUrls));
                
                $excludeCondition = " p.url NOT IN (" . implode(', ', $placeholders) . ")";
            }
        }
    
        return [$excludeCondition, $excludeUrls];
    }
}