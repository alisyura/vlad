<?php

/**
 * Модель для работы с данными постов.
 *
 * Предоставляет методы для подсчета, получения и фильтрации
 * опубликованных постов и страниц из базы данных.
 */
class PostModel {
    private $db;
    
    /**
     * Конструктор PostModel.
     *
     * @param PDO $pdo Объект подключения к базе данных.
     */
    public function __construct(PDO $pdo) {
        // Инициализация подключения к БД
        $this->db = $pdo;
    }
    
    /**
     * Подсчитывает общее количество опубликованных постов.
     *
     * @return int Общее количество постов.
     */
    public function countAllPosts() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM posts 
            WHERE status = 'published' AND article_type = 'post'
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

    /**
     * Подсчитывает количество опубликованных постов, связанных с определенным тегом.
     *
     * @param string $tag_url URL-адрес тега.
     * @return int Количество постов, связанных с тегом.
     */
    public function countAllPostsByTag($tag_url) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM posts p
            INNER JOIN post_tag pt ON p.id = pt.post_id
            INNER JOIN tags t ON pt.tag_id = t.id
            WHERE p.status = 'published' 
              AND p.article_type = 'post'
              AND t.url = :tag_url
        ");
        
        $stmt->execute([':tag_url' => $tag_url]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }

    /**
     * Подсчитывает количество опубликованных постов, связанных с определенной категорией.
     *
     * @param string $category_url URL-адрес категории.
     * @return int Количество постов, связанных с категорией.
     */
    public function countAllPostsByCategory($category_url) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM posts p
            INNER JOIN post_category pc ON p.id = pc.post_id
            INNER JOIN categories c ON pc.category_id = c.id
            WHERE p.status = 'published' 
              AND p.article_type = 'post'
              AND c.url = :category_url
        ");
    
        $stmt->execute([':category_url' => $category_url]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return (int)$row['total'];
    }

    /**
     * Получает список всех опубликованных постов с поддержкой пагинации.
     *
     * @param int $posts_per_page Количество постов на страницу.
     * @param int $page Номер страницы (по умолчанию 1).
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPosts($posts_per_page, $page = 1) {
        // Вычисляем offset
        $offset = ($page - 1) * $posts_per_page;

        $sql = "
            SELECT 
                p.url AS url,
                p.title AS title,
                p.content AS content,
                p.updated_at AS updated_at,
                c.url AS category_url,
                c.name AS category_name,
                m.file_path AS image,
                p.likes_count AS likes,
                p.dislikes_count AS dislikes
            FROM
                posts AS p
            INNER JOIN
                post_category AS pc ON pc.post_id = p.id
            INNER JOIN
                categories AS c ON c.id = pc.category_id
            LEFT JOIN
                media AS m ON m.id = p.thumbnail_media_id
            WHERE
                p.status = 'published' AND
                p.article_type = 'post'
            ORDER BY
                p.updated_at DESC
            LIMIT :limit OFFSET :offset";
        
             //echo debugPDO($sql, ['limit' => $posts_per_page, 'offset' => $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получает данные одного опубликованного поста по его URL-адресу.
     *
     * Включает информацию о категории, тегах и миниатюре. Теги преобразуются
     * в массив.
     *
     * @param string $post_url URL-адрес поста.
     * @return array|false Ассоциативный массив с данными поста или false, если пост не найден.
     */
    public function getPostByUrl($post_url) {
        $stmt = $this->db->prepare("
        SELECT 
            p.url AS url,
            p.title AS title,
            p.content AS content,
            p.updated_at AS updated_at,
            c.url AS category_url,
            c.name AS category_name,
            m.file_path AS image,
            GROUP_CONCAT(CONCAT(t.name, '|', t.url)) AS tags
        FROM posts p
        INNER JOIN post_category pc ON pc.post_id = p.id
        INNER JOIN categories c ON pc.category_id = c.id
        LEFT JOIN post_tag pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        LEFT JOIN media m ON m.post_id = p.id
        WHERE p.url = :url AND p.status = 'published' AND p.article_type = 'post'
        GROUP BY 
            p.id, 
            p.url, 
            p.title, 
            p.content, 
            p.updated_at,
            c.url,
            c.name,
            m.file_path");

        $stmt->execute([':url' => $post_url]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        return $this->fillTags($row);
    }

    /**
     * Получает данные одной опубликованной страницы по ее URL-адресу.
     *
     * Включает информацию о тегах. Теги преобразуются в массив.
     *
     * @param string $page_url URL-адрес страницы.
     * @return array|false Ассоциативный массив с данными страницы или false, если страница не найдена.
     */
    public function getPageByUrl($page_url) {
        $sql = "
        SELECT 
            p.url AS url,
            p.title AS title,
            p.content AS content,
            p.updated_at AS updated_at,
            GROUP_CONCAT(CONCAT(t.name, '|', t.url)) AS tags
        FROM posts p
        LEFT JOIN post_tag pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        WHERE p.url = :url AND p.status = 'published' AND p.article_type = 'page'
        GROUP BY 
            p.id, 
            p.url, 
            p.title, 
            p.content, 
            p.updated_at";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':url' => $page_url]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        return $this->fillTags($row);
    }

    /**
     * Преобразует строку тегов в массив.
     *
     * @param array $row Ассоциативный массив с данными поста, содержащий ключ 'tags'.
     * @return array Обновленный массив с преобразованными данными тегов.
     */
    private function fillTags($row)
    {
        if (!empty($row['tags'])) {
            $tags = array_map(function($pair) {
                list($name, $url) = explode('|', trim($pair));
                return [
                    'name' => $name,
                    'url' => $url
                ];
            }, explode(',', $row['tags']));
            $row['tags'] = $tags;
        }
        else
        {
            unset($row['tags']);
        }

        return $row;
    }

    /**
     * Извлекает список опубликованных постов для указанной категории с поддержкой пагинации.
     *
     * @param string $cat_url URL-адрес категории.
     * @param bool $show_link_next Определяет, возвращать полный контент или отрывок.
     * @param int $posts_per_page Количество постов на страницу.
     * @param int $page Номер страницы (по умолчанию 1).
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPostsByCategory(string $cat_url, bool $show_link_next,
        int $posts_per_page, int $page = 1): array
    {
        $excerpt_len = Config::get('posts.exerpt_len') + 50;
        $offset = ($page - 1) * $posts_per_page;

        $sql = "
            SELECT
                p.url AS url,
                p.title AS title,
                IF(:show_excerpt, SUBSTRING(p.content, 1, :excerpt_len), p.content) AS content,
                p.updated_at AS updated_at,
                c.url AS category_url,
                c.name AS category_name,
                m.file_path AS image,
                p.likes_count AS likes,
                p.dislikes_count AS dislikes
            FROM
                posts AS p
            INNER JOIN
                post_category AS pc ON pc.post_id = p.id
            INNER JOIN
                categories AS c ON c.id = pc.category_id
            LEFT JOIN
                media AS m ON m.id = p.thumbnail_media_id
            WHERE
                p.status = 'published' AND
                p.article_type = 'post' AND
                c.url = :cat_url
            ORDER BY
                p.updated_at DESC
            LIMIT :limit OFFSET :offset";

        Logger::debug(debugPDO($sql, [
            ':cat_url' => $cat_url,
            ':limit' => $posts_per_page,
            ':offset' => $offset,
            ':show_excerpt' => (int) $show_link_next,
            ':excerpt_len' => $excerpt_len
        ]));

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cat_url' => $cat_url,
            ':limit' => $posts_per_page,
            ':offset' => $offset,
            ':show_excerpt' => $show_link_next,
            ':excerpt_len' => $excerpt_len
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Извлекает список опубликованных постов для указанного тега с поддержкой пагинации.
     *
     * @param string $tag_url URL-адрес тега.
     * @param int $posts_per_page Количество постов на страницу.
     * @param int $page Номер страницы (по умолчанию 1).
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPostsByTag(string $tag_url, int $posts_per_page, int $page = 1): array
    {
        $offset = ($page - 1) * $posts_per_page;

        $sql = "
            SELECT 
                p.url AS url,
                p.title AS title,
                p.content AS content,
                DATE_FORMAT(p.updated_at, '%Y-%m-%d') AS updated_at,
                p.meta_description AS description,
                t.url AS tag_url,
                t.name AS tag_name,
                c.url AS category_url,
                c.name AS category_name,
                m.file_path AS image,
                p.likes_count AS likes,
                p.dislikes_count AS dislikes,
                u.name AS user_name
            FROM
                posts AS p
            INNER JOIN
                post_tag AS pt ON pt.post_id = p.id
            INNER JOIN
                tags AS t ON t.id = pt.tag_id
            INNER JOIN
                post_category AS pc ON pc.post_id = p.id
            INNER JOIN
                categories AS c ON c.id = pc.category_id
            LEFT JOIN
                media AS m ON m.id = p.thumbnail_media_id
            LEFT JOIN
                users AS u ON u.id = p.user_id
            WHERE
                p.status = 'published' AND
                p.article_type = 'post' AND
                t.url = :tag_url
            ORDER BY
                p.updated_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Передаем все параметры одним массивом в метод execute()
        $stmt->execute([
            ':tag_url' => $tag_url,
            ':limit' => $posts_per_page,
            ':offset' => $offset
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}