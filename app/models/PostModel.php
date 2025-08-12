<?php

class PostModel {
    private $db;
    
    public function __construct() {
        // Инициализация подключения к БД
        $dbHost = Config::getDbHost('DB_HOST');
        $dbName = Config::getDbHost('DB_NAME');
        $dbUser = Config::getDbHost('DB_USER');
        $dbPass = Config::getDbHost('DB_PASS');

        $this->db = new PDO('mysql:host='.$dbHost.';dbname='.$dbName, $dbUser, $dbPass);
    }
    
    public function countAllPosts() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM posts 
            WHERE status = 'published' AND article_type = 'post'
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }

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

    public function getAllPosts($page = 1) {
        //берем немного больше, чтобы учесть длинные слова.
        $posts_per_page = Config::getPostsCfg('posts_per_page');

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

    public function getAllPostsByCategory($cat_url, $show_link_next, $page = 1) {
        $posts_per_page = Config::getPostsCfg('posts_per_page');
        $offset = ($page - 1) * $posts_per_page;
    
        if ($show_link_next) {
            $exerpt_len = Config::getPostsCfg('exerpt_len') + 50;
            $content = "SUBSTRING(p.content, 1, $exerpt_len) AS content";
        } else {
            $content = "p.content AS content";
        }
    
        $sql = "
            SELECT 
                p.url AS url,
                p.title AS title,
                $content,
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
            ':offset' => $offset
        ]));
    
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat_url', $cat_url, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPostsByTag($tag_url, $page = 1) {
        // проверяем, существует ли такй тэг
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tags WHERE url = :tag_url");
        $stmt->execute([':tag_url' => $tag_url]);
        if ((int)$stmt->fetchColumn() === 0) {
            return []; // такого тэга нет
        }

        $posts_per_page = Config::getPostsCfg('posts_per_page');
        $offset = ($page - 1) * $posts_per_page;
    
        $sql = "
            SELECT 
                p.url AS url,
                p.title AS title,
                p.content AS content,
                DATE_FORMAT(p.updated_at, '%Y-%m-%d') AS updated_at,
                p.description AS description,
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
        $stmt->bindValue(':tag_url', $tag_url, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Сохраняем пост предложенный посетителем
     * 
     */
    public function savePost($data, $imagePath = null)
    {
        $sql = "
            INSERT INTO
                posts (content, user_id, title, created_at, updated_at, status, article_type)
            VALUES
                (?, ?, ?, NOW(), NOW(), 'pending', 'post');";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            //$data['email'],
            $data['text'],
            $data['video_link'] ?? '',
            $imagePath ?? ''
        ]);

        return $this->pdo->lastInsertId();
    }

    private function getAdminUserId()
    {
        $stmt = $this->db->query("SELECT u.id
            FROM users u
            JOIN roles r ON u.role = r.id
            WHERE r.name = 'Administrator'
            ORDER BY u.id ASC
            LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSitemapData()
    {
        $sql="(
            SELECT 
                'post' AS type,
                c.id AS category_id,
                c.name AS category_name,
                c.url AS category_url,
                p.title AS post_title,
                p.url AS post_url,
                p.updated_at AS updated_at
            FROM 
                categories c
            JOIN 
                post_category pc ON c.id = pc.category_id
            JOIN 
                posts p ON pc.post_id = p.id
            WHERE 
                p.article_type = 'post'
                AND p.status = 'published'
        )
        UNION
        (
            SELECT 
                'page' AS type,
                NULL AS category_id,
                NULL AS category_name,
                NULL AS category_url,
                p.title AS post_title,
                p.url AS post_url,
                p.updated_at AS updated_at
            FROM 
                posts p
            WHERE 
                p.article_type = 'page'
                AND p.status = 'published'
        )
        ORDER BY 
            FIELD(type, 'post', 'page'), -- Сначала посты, потом страницы
            category_id ASC,
            updated_at deSC;";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}