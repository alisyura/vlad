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
     * @return int Общее количество постов.
     */
    public function getTotalPostsCount() {
        $sql = "SELECT COUNT(id) AS total_posts FROM posts WHERE article_type='post'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
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
     * @param int $limit Количество постов на страницу.
     * @param int $offset Смещение (сколько постов пропустить).
     * @return array Список постов, каждый из которых содержит массив категорий и массив тегов.
     */
    public function getPosts(int $limit, int $offset) {
        $sql = "SELECT
                    p.id,
                    p.title,
                    p.url,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    u.name AS author_name,
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
                    article_type = 'post'
                GROUP BY
                    p.id
                ORDER BY
                    p.id DESC
                LIMIT :limit OFFSET :offset"; // Добавляем LIMIT и OFFSET для пагинации

        try {
            $stmt = $this->db->prepare($sql);
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
                        if (strpos($pair, '||') !== false) { // Убедимся, что пара валидна
                            list($name, $url) = explode('||', $pair, 2); // Ограничиваем explode до 2 частей
                            $post['categories'][] = ['name' => $name, 'url' => $url];
                        }
                    }
                }
                unset($post['category_data']); // Удаляем сырые данные после обработки

                // Разбираем данные тегов
                $post['tags'] = [];
                if (!empty($post['tag_data'])) {
                    $tag_pairs = explode(';;', $post['tag_data']);
                    foreach ($tag_pairs as $pair) {
                         if (strpos($pair, '||') !== false) { // Убедимся, что пара валидна
                            list($name, $url) = explode('||', $pair, 2); // Ограничиваем explode до 2 частей
                            $post['tags'][] = ['name' => $name, 'url' => $url];
                        }
                    }
                }
                unset($post['tag_data']); // Удаляем сырые данные после обработки

                $posts[] = $post;
            }

            return $posts;

        } catch (PDOException $e) {
            Logger::error("Error fetching paginated posts in AdminPostsModel: " . $e->getMessage());
            return [];
        }
    }
}