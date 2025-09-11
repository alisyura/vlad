<?php
// app/models/PostsListModel.php

class PostsListModel extends BaseModel {
    /**
     * Получает общее количество постов. Необходимо для пагинации.
     *
     * @param string $article_type Тип статьи. post или page
     * @param bool $showThrash Получить список удаленных или активных постов.
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount(string $article_type, bool $showThrash = false) {
        [$excludeCondition, $excludeUrls] = $this->getExcludeConditionAndUrls($article_type);
        
        // Создаем массив для всех условий WHERE
        $conditions = [
            'p.article_type = :article_type'
        ];
        
        // Добавляем условие по статусу
        $conditions[] = $showThrash ? "p.status = 'deleted'" : "p.status <> 'deleted'";
        
        // Добавляем условие исключения URL, если оно есть
        if (!empty($excludeCondition)) {
            $conditions[] = $excludeCondition;
        }

        // Собираем запрос
        $sql = "SELECT COUNT(p.id) AS total_posts 
                FROM posts AS p
                WHERE " . implode(' AND ', $conditions);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':article_type', $article_type, PDO::PARAM_STR);
            
            // Связываем параметры URL, если они есть
            if (!empty($excludeUrls)) {
                foreach ($excludeUrls as $index => $url) {
                    $stmt->bindParam(":url{$index}", $excludeUrls[$index], PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total_posts'];
            
        } catch (PDOException $e) {
            Logger::error("Error fetching total posts count: " . $e->getTraceAsString());
            throw $e;
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
     * @param bool $showThrash Получить список удаленных или активных постов.
     * @return array Список постов, каждый из которых содержит массив категорий и массив тегов.
     */
    public function getPostsList(string $article_type, int $limit, int $offset,
                             string $sortBy = 'updated_at', string $sortOrder = 'DESC',
                             bool $showThrash = false) {
        // Получаем условие исключения и URL-адреса, используя существующий метод
        [$excludeCondition, $excludeUrls] = $this->getExcludeConditionAndUrls($article_type);

        // Допустимые поля для сортировки и их соответствие в базе данных
        $allowedSortColumns = [
            'id' => 'p.id',
            'title' => 'p.title',
            'author' => 'u.name',
            'categories' => 'category_names_concat', // Сортируем по конкатенированному списку категорий
            'tags' => 'tag_names_concat', // Сортируем по конкатенированному списку тегов
            'status' => 'p.status',
            'updated_at' => 'p.updated_at'
        ];

        // Проверяем, что $sortBy является допустимой колонкой
        $orderByColumn = $allowedSortColumns['updated_at']; // По умолчанию
        if (array_key_exists($sortBy, $allowedSortColumns)) {
            $orderByColumn = $allowedSortColumns[$sortBy];
        }

        // Проверяем, что $sortOrder является допустимым направлением
        $sortOrder = strtoupper($sortOrder); // Приводим к верхнему регистру
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC'; // По умолчанию DESC
        }

        // Собираем массив условий для части WHERE
        $conditions = [
            'p.article_type = :article_type',
            $showThrash ? "p.status = 'deleted'" : "p.status <> 'deleted'"
        ];

        // Добавляем условие исключения, если оно есть
        if (!empty($excludeCondition)) {
            $conditions[] = $excludeCondition;
        }

        $sql = "SELECT
                     p.id,
                     p.title,
                     p.url,
                     p.status,
                     p.created_at,
                     p.updated_at,
                     p.article_type,
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
                     " . implode(' AND ', $conditions) . "
                 GROUP BY
                     p.id
                 ORDER BY
                     {$orderByColumn} {$sortOrder}
                 LIMIT :limit OFFSET :offset";

        try {

            Logger::debug("getPosts. $article_type, $limit, $offset,
                             $sortBy, $sortOrder. $sql");
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':article_type', $article_type, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

            // Связываем параметры URL, если они есть, для исключения постов/страниц
            if (!empty($excludeUrls)) {
                foreach ($excludeUrls as $index => $url) {
                    $stmt->bindParam(":url{$index}", $excludeUrls[$index], PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $rawPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Вызываем вспомогательный метод для парсинга данных
            return $this->parsePostData($rawPosts);

        } catch (PDOException $e) {
            Logger::error("Error fetching paginated posts in AdminPostsModel: " . $e->getTraceAsString());
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