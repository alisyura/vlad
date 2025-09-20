<?php

// app/controllers/SitemapController.php
class SitemapController {
    use ShowClientErrorViewTrait;
    
    private $db;
    private $uri;
    private $max_urls;
    private Request $request;
    private ViewAdmin $view;
    private SitemapModel $model;

    public function __construct(Request $request, ViewAdmin $view, SitemapModel $sitemapModel) {
        $this->db = Database::getConnection();

        $this->request = $request;
        $this->view = $view;
        $this->model = $sitemapModel;

        $this->uri = $request->getBaseUrl();
        $this->max_urls = Config::get('posts.max_urls_in_sitemap');
    }

    /*
    * Страница Карта сайта
    */
    public function showSitemap() {
        try {
            $posts = $this->model->getSitemapData();
            if (!$posts) {
                $this->showErrorView('Страница не найдена', '', 404);
                return;
            }

            $result = [
                'post' => [],
                'page' => [
                    'pages' => []
                ]
            ];
            
            foreach ($posts as $row) {
                if ($row['type'] === 'post') {
                    // Это обычный пост с категорией
                    $categoryUrl = $row['category_url'];
            
                    if (!isset($result['post'][$categoryUrl])) {
                        $result['post'][$categoryUrl] = [
                            'name' => $row['category_name'],
                            'url' => $row['category_url'],
                            'posts' => []
                        ];
                    }
            
                    $result['post'][$categoryUrl]['posts'][] = [
                        'title' => $row['post_title'],
                        'url' => $row['post_url']
                    ];
            
                } elseif ($row['type'] === 'page') {
                    // Это страница без категории
                    $result['page']['pages'][] = [
                        'title' => $row['post_title'],
                        'url' => $row['post_url']
                    ];
                }
            }

            $contentData = [
                'data' => $result,
                'full_url' => $this->request->getRequestUrl(),
                //'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'sitemap',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->getRequestUrl(),
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
                    //'posts' => $posts,
                    'styles' => [
                        'sitemap.css'
                    ],
                    'jss' => [
                        'sitemap.js'
                    ]
                ]
            ];

            $this->view->renderClient('pages/sitemap.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showSitemap: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    public function generateSitemapIndexXml()
    {
        header('Content-Type: application/xml; charset=utf-8');

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
        header('Content-Type: application/xml; charset=utf-8');

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