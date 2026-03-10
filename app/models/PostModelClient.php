<?php

/**
 * Модель для работы с данными постов.
 *
 * Предоставляет методы для подсчета, получения и фильтрации
 * опубликованных постов и страниц из базы данных.
 */
class PostModelClient {
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
    public function countAllPostsForHome() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM posts 
            WHERE created_at <= NOW() AND status = 'published' AND article_type = 'post'
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
            WHERE p.created_at <= NOW()
                AND p.status = 'published' 
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
     * @param string|null $category_url URL-адрес категории.
     * @param int|null $min_likes Обирает посты с мин кол-вом лайков.
     * @return int Количество постов, связанных с категорией.
     */
    public function countAllPostsByCategory(?string $category_url = null, ?int $min_likes = null) 
    {
        // Переиспользуем наш новый приватный метод!
        [$whereSql, $params] = $this->prepareWhereConditions($category_url, $min_likes);

        // Если есть категория, нужен JOIN, если нет - считаем по всей таблице
        $joinSql = ($category_url !== null) 
            ? "INNER JOIN post_category pc ON p.id = pc.post_id INNER JOIN categories c ON pc.category_id = c.id" 
            : "";

        $sql = "SELECT COUNT(*) as total FROM posts p $joinSql WHERE $whereSql";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }

    /**
     * Получает список всех опубликованных постов с поддержкой пагинации.
     *
     * @param int $postsPerPage Количество постов на страницу.
     * @param int $excerptLen Длина анонса.
     * @param array $excerptCategories Массив урл категорий у которых выводить анонс.
     * @param int $page Номер страницы (по умолчанию 1).
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPostsForHome(int $postsPerPage, int $excerptLen, 
        array $excerptCategories, int $page = 1): array
    {
        $offset = ($page - 1) * $postsPerPage;
        
        // 1. Условия для главной (обычно без фильтра по категории и лайкам, но статус 'published')
        // Мы можем вызвать prepareWhereConditions(null, null)
        [$whereSql, $params] = $this->prepareWhereConditions(null, null);
        
        $params[':limit'] = $postsPerPage;
        $params[':offset'] = $offset;

        // 2. Логика анонсов (теперь с исправленными именами параметров внутри)
        $contentLogic = $this->prepareContentLogic($excerptCategories, $excerptLen, $params);

        $sql = "SELECT p.id, p.url, p.title, p.updated_at,
                    p.likes_count AS likes, p.dislikes_count AS dislikes,
                    m.file_path AS image, c.url AS category_url, c.name AS category_name,
                    {$contentLogic['content']} AS content, 
                    {$contentLogic['is_excerpted']} AS is_excerpted
                FROM posts AS p
                -- Используем LEFT JOIN, так как на главной могут быть посты из разных категорий
                LEFT JOIN post_category AS pc ON pc.post_id = p.id
                LEFT JOIN categories AS c ON c.id = pc.category_id
                LEFT JOIN media AS m ON m.id = p.thumbnail_media_id
                WHERE $whereSql
                ORDER BY p.updated_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
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
            p.id AS id,
            p.url AS url,
            p.title AS title,
            p.content AS content,
            p.comment AS comment,
            p.updated_at AS updated_at,
            c.url AS category_url,
            c.name AS category_name,
            m.file_path AS image,
            p.meta_title AS meta_title,
            p.meta_keywords AS meta_keywords,
            p.meta_description AS meta_description,
            GROUP_CONCAT(CONCAT(t.name, '|', t.url)) AS tags
        FROM posts p
        INNER JOIN post_category pc ON pc.post_id = p.id
        INNER JOIN categories c ON pc.category_id = c.id
        LEFT JOIN post_tag pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        LEFT JOIN media m ON m.id = p.thumbnail_media_id
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
            p.meta_title AS meta_title,
            p.meta_keywords AS meta_keywords,
            p.meta_description AS meta_description,
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
     * @param int $postsPerPage Количество постов на страницу.
     * @param int $excerptLen Длина анонса.
     * @param array $excerptCategories Массив урл категорий у которых выводить анонс.
     * @param string|null $catUrl URL-адрес категории.
     * @param int $page Номер страницы (по умолчанию 1).
     * @param int|null $minLikes Обирает посты с мин кол-вом лайков.
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPostsByCategory(int $postsPerPage, int $excerptLen, 
        array $excerptCategories, ?string $catUrl = null, int $page = 1, 
        ?int $minLikes = null): array
    {
        $offset = ($page - 1) * $postsPerPage;
        
        // Собираем параметры и условия через вспомогательный метод
        [$whereSql, $params] = $this->prepareWhereConditions($catUrl, $minLikes);
        
        $params[':limit'] = $postsPerPage;
        $params[':offset'] = $offset;

        // Логика обрезки
        $contentLogic = $this->prepareContentLogic($excerptCategories, $excerptLen, $params);

        $sql = "SELECT p.id, p.url, p.title, p.updated_at,
                    p.likes_count AS likes, p.dislikes_count AS dislikes,
                    m.file_path AS image, c.url AS category_url, c.name AS category_name,
                    {$contentLogic['content']} AS content, 
                    {$contentLogic['is_excerpted']} AS is_excerpted
                FROM posts AS p
                LEFT JOIN post_category AS pc ON pc.post_id = p.id
                LEFT JOIN categories AS c ON c.id = pc.category_id
                LEFT JOIN media AS m ON m.id = p.thumbnail_media_id
                WHERE $whereSql
                ORDER BY p.updated_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function prepareWhereConditions(?string $cat_url, ?int $min_likes): array
    {
        $clauses = ["p.created_at <= NOW()", "p.status = 'published'", "p.article_type = 'post'"];
        $params = [];

        if ($cat_url !== null) {
            $clauses[] = "c.url = :cat_url";
            $params[':cat_url'] = $cat_url;
        }
        if ($min_likes !== null) {
            $clauses[] = "p.likes_count >= :min_likes";
            $params[':min_likes'] = $min_likes;
        }

        return [implode(" AND ", $clauses), $params];
    }

    private function prepareContentLogic(array $categories, ?int $excerpt_len, array &$params): array
    {
        if ($excerpt_len === null || empty($categories)) {
            return ['content' => 'p.content', 'is_excerpted' => '0'];
        }

        $quoted = "'" . implode("','", array_map('addslashes', $categories)) . "'";
        
        // Создаем два разных плейсхолдера с одним и тем же значением
        $params[':ex_len_content'] = $excerpt_len;
        $params[':ex_len_flag'] = $excerpt_len;

        return [
            'content' => "CASE 
                WHEN c.url IN ($quoted) THEN SUBSTRING(p.content, 1, :ex_len_content) 
                ELSE p.content 
            END",
            'is_excerpted' => "CASE 
                WHEN c.url IN ($quoted) AND CHAR_LENGTH(p.content) > :ex_len_flag THEN 1 
                ELSE 0 
            END"
        ];
    }

    /**
     * Извлекает список опубликованных постов для указанного тега с поддержкой пагинации.
     *
     * @param string $tagUrl URL-адрес тега.
     * @param int $postsPerPage Количество постов на страницу.
     * @param int $excerptLen Длина анонса.
     * @param array $excerptCategories Массив урл категорий у которых выводить анонс.
     * @param int $page Номер страницы (по умолчанию 1).
     * @return array Массив ассоциативных массивов с данными о постах.
     */
    public function getAllPostsByTag(string $tagUrl, int $postsPerPage, 
        int $excerptLen, array $excerptCategories, int $page = 1): array
    {
        $offset = ($page - 1) * $postsPerPage;

        // 1. Используем наш метод для базовых условий (status, article_type)
        // Передаем null в категорию и лайки, так как здесь фильтр по тегу
        [$whereSql, $params] = $this->prepareWhereConditions(null, null);
        
        // Добавляем специфичное условие для тега
        $whereSql .= " AND t.url = :tag_url";
        $params[':tag_url'] = $tagUrl;
        $params[':limit'] = $postsPerPage;
        $params[':offset'] = $offset;

        // 2. Логика анонсов (использует те же CASE WHEN)
        $contentLogic = $this->prepareContentLogic($excerptCategories, $excerptLen, $params);

        $sql = "
            SELECT 
                p.id, p.url, p.title,
                {$contentLogic['content']} AS content,
                {$contentLogic['is_excerpted']} AS is_excerpted,
                DATE_FORMAT(p.updated_at, '%Y-%m-%d') AS updated_at,
                p.meta_description AS description,
                t.url AS tag_url, t.name AS tag_name,
                c.url AS category_url, c.name AS category_name,
                m.file_path AS image,
                p.likes_count AS likes, p.dislikes_count AS dislikes,
                u.name AS user_name
            FROM posts AS p
            INNER JOIN post_tag AS pt ON pt.post_id = p.id
            INNER JOIN tags AS t ON t.id = pt.tag_id
            INNER JOIN post_category AS pc ON pc.post_id = p.id
            INNER JOIN categories AS c ON c.id = pc.category_id
            LEFT JOIN media AS m ON m.id = p.thumbnail_media_id
            LEFT JOIN users AS u ON u.id = p.user_id
            WHERE $whereSql
            ORDER BY p.updated_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Привязываем параметры с проверкой типов (для LIMIT/OFFSET)
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}