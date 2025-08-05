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
            error_log("Database connection error: " . $e->getMessage());
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
            Logger::error("Error fetching total posts count: " . $e->getMessage());
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
            Logger::error("Error fetching paginated posts in AdminPostsModel: " . $e->getMessage());
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
            Logger::error("Error fetching post by ID: " . $e->getMessage());
            return null;
        }
    }
    
    // --- НОВЫЕ МЕТОДЫ ДЛЯ СОЗДАНИЯ ПОСТА ---

    /**
     * Получает все категории из базы данных.
     * @return array Список категорий.
     */
    public function getAllCategories(): array {
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Error fetching all categories: " . $e->getMessage());
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
            Logger::error("Error fetching all tags: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Вставляет новый пост в базу данных.
     * @param array $data Данные поста.
     * @return int|false ID нового поста или false в случае ошибки.
     */
    public function createPost(array $data) {
        // Установка значений по умолчанию, если они не предоставлены
        $data['article_type'] = $data['article_type'] ?? 'post';
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO posts (user_id, article_type, status, title, content, url, meta_keywords, meta_description, created_at, updated_at)
                VALUES (:user_id, :article_type, :status, :title, :content, :url, :meta_keywords, :meta_description, :created_at, :updated_at)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':article_type' => $data['article_type'],
                ':status' => $data['status'],
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':url' => $data['url'],
                ':meta_keywords' => $data['meta_keywords'],
                ':meta_description' => $data['meta_description'],
                ':created_at' => $data['created_at'],
                ':updated_at' => $data['updated_at']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            Logger::error("Error creating post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Связывает пост с категориями.
     * @param int $postId ID поста.
     * @param array $categoryIds Массив ID категорий.
     * @return bool Успех операции.
     */
    public function linkPostToCategories(int $postId, array $categoryIds): bool {
        if (empty($categoryIds)) {
            return true;
        }
        $sql = "INSERT IGNORE INTO post_category (post_id, category_id) VALUES ";
        $values = [];
        foreach ($categoryIds as $categoryId) {
            $values[] = "(:post_id, :category_id_" . $categoryId . ")";
        }
        $sql .= implode(', ', $values);
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            foreach ($categoryIds as $categoryId) {
                $stmt->bindParam(':category_id_' . $categoryId, $categoryId, PDO::PARAM_INT);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Error linking post to categories: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Связывает пост с тегами. Если тег не существует, он создается.
     * @param int $postId ID поста.
     * @param array $tagUrls Массив URL тегов.
     * @return bool Успех операции.
     */
    public function linkPostToTags(int $postId, array $tagUrls): bool {
        if (empty($tagUrls)) {
            return true;
        }

        $tagIds = [];
        $existingTags = [];
        $newTags = [];
        
        // Получаем существующие теги по URL
        $placeholders = implode(',', array_fill(0, count($tagUrls), '?'));
        $sql = "SELECT id, url FROM tags WHERE url IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagUrls);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tag) {
            $existingTags[$tag['url']] = $tag['id'];
        }

        foreach ($tagUrls as $tagUrl) {
            if (isset($existingTags[$tagUrl])) {
                $tagIds[] = $existingTags[$tagUrl];
            } else {
                // Если тега нет, создаем его
                $newTagSql = "INSERT INTO tags (name, url) VALUES (?, ?)";
                $newTagStmt = $this->db->prepare($newTagSql);
                $tagName = str_replace('-', ' ', $tagUrl); // Простая логика для имени
                $newTagStmt->execute([$tagName, $tagUrl]);
                $tagIds[] = $this->db->lastInsertId();
            }
        }

        if (empty($tagIds)) {
            return true;
        }

        // Связываем пост с тегами
        $sql = "INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES ";
        $values = [];
        foreach ($tagIds as $tagId) {
            $values[] = "(:post_id, :tag_id_" . $tagId . ")";
        }
        $sql .= implode(', ', $values);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            foreach ($tagIds as $tagId) {
                $stmt->bindParam(':tag_id_' . $tagId, $tagId, PDO::PARAM_INT);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Error linking post to tags: " . $e->getMessage());
            return false;
        }
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
