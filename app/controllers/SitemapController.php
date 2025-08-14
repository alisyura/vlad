<?php

class SitemapController {
    private $db;
    private $uri;
    private $max_urls;

    public function __construct() {
        header('Content-Type: application/xml; charset=utf-8');

        $this->db = Database::getConnection();

        $this->uri = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
        $this->max_urls = Config::getPostsCfg('max_urls_in_sitemap');
    }

    public function generateSitemapIndexXml()
    {
        // Чанки для постов
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM posts
            WHERE status = 'published' AND article_type='post'");
        $row = $stmt->fetch();
        $total_posts = $row['total'];
        $chunks_posts = ceil($total_posts / $this->max_urls);

        // Чанки для страниц
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM posts
            WHERE status = 'published' AND article_type='page'");
        $row = $stmt->fetch();
        $total_pages = $row['total'];
        $chunks_pages = ceil($total_pages / $this->max_urls);

        $content = View::render('../app/views/sitemap/sitemap-index.xml.php', [
            'chunks_posts' => $chunks_posts,
            'chunks_pages' => $chunks_pages,
            'url' => $this->uri
        ]);

        echo $content;
    }

    public function generateSitemapPartXml($type, $page)
    {
        $offset = ($page - 1) * $this->max_urls;

        switch ($type) {
            case 'posts':
                $posts = $this->getPostsByOffsetNum($offset, 'post');
                $render_parts = [
                    'posts' => $posts,
                    'posts_priority' => "0.6",
                    'changefreq_posts' => 'weekly',
                    'url' => $this->uri,
                    'pages' => []
                ];
                break;
            case 'pages':
                $pages = $this->getPostsByOffsetNum($offset, 'page');
                $render_parts = [
                    'pages' => $pages,
                    'pages_priority' => "0.5",
                    'changefreq_pages' => 'monthly',
                    'url' => $this->uri,
                    'posts' => []
                ];
                break;
        }

        $content = View::render('../app/views/sitemap/sitemap-part.xml.php', $render_parts);

        echo $content;
    }

    /**
     * Получает часть постов/страниц для указанного типа и страницы.
     * 
     * @param int $offset Неотрицательное смещение
     * @param string 'post'|'page' $type Тип записей
     * @return array Массив URL-ов и дат обновления
     */
    private function getPostsByOffsetNum(int $offset, string $type) : array
    {
        $sql = "
            SELECT url, DATE_FORMAT(updated_at, '%Y-%m-%dT%T+00:00') AS updated_at FROM posts 
            WHERE status = 'published' AND article_type = :type
            LIMIT :max_urls OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':max_urls', $this->max_urls, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}