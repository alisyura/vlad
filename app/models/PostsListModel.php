<?php
// app/models/PostsListModel.php

class PostsListModel extends BaseModel {
    /**
     * Получает общее количество постов. Необходимо для пагинации.
     *
     * @param string $article_type Тип статьи. post или page
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount(string $article_type) {
        // Определяем массив исключений в зависимости от типа статьи
        $configKey = null;
        if ($article_type === 'page') {
            $configKey = 'admin.PagesToExclude';
        } elseif ($article_type === 'post') {
            $configKey = 'admin.PostsToExclude';
        }

        $excludeUrls = [];
        $excludeCondition = '';

        // Если ключ конфигурации определён
        if ($configKey) {
            $rawExcludeUrls = \Config::get($configKey);
            $excludeUrls = array_filter($rawExcludeUrls, 'strlen');

            // Проверяем, что массив не пустой после фильтрации
            if (!empty($excludeUrls)) {
                $placeholders = [];
                foreach ($excludeUrls as $index => $url) {
                    $placeholders[] = ":url{$index}";
                }
                $excludeCondition = " AND url NOT IN (" . implode(', ', $placeholders) . ")";
            }
        }

        $sql = "SELECT COUNT(id) AS total_posts 
                FROM posts 
                WHERE article_type = :article_type AND status <> 'deleted' {$excludeCondition}";

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
     * @return array Список постов, каждый из которых содержит массив категорий и массив тегов.
     */
    public function getPostsList(string $article_type, int $limit, int $offset,
                             string $sortBy = 'updated_at', string $sortOrder = 'DESC') {
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

        // вставляем параметры для исключения постов/страниц из выборки
        // Определяем массив исключений в зависимости от типа статьи
        $configKey = null;
        if ($article_type === 'page') {
            $configKey = 'admin.PagesToExclude';
        } elseif ($article_type === 'post') {
            $configKey = 'admin.PostsToExclude';
        }

        // Если ключ конфигурации определён
        if ($configKey) {
            $rawExcludeUrls = \Config::get($configKey);
            $excludeUrls = array_filter($rawExcludeUrls, 'strlen');

            // Проверяем, что массив не пустой после фильтрации
            if (!empty($excludeUrls)) {
                $placeholders = [];
                foreach ($excludeUrls as $index => $url) {
                    $placeholders[] = ":url{$index}";
                }
                $excludeCondition = " AND p.url NOT IN (" . implode(', ', $placeholders) . ")";
            }
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
                     article_type = :article_type
                     AND status <> 'deleted'
                     {$excludeCondition}
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
                // если хотите именно строку, а не массив объектов.
                // Или можно продолжать использовать $post['categories'] для построения ссылок.
                // В контроллере вы уже делаем это, так что category_names_concat не строго нужен для вывода,
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
            throw $e;
        }
    }
}