<?php

class PostController {
    /**
     * Экземпляр модели
     */
    private $model;
    /**
     * Адрес сайта
     */
    private $uri;
    /**
     * Url с которого пришел запрос
     */
    private $requestUrl;

    public function __construct() {
        $this->model = new PostModel();
        $this->uri = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
        $this->requestUrl = sprintf("%s/%s", rtrim($this->uri, '/'), ltrim($_SERVER['REQUEST_URI'], '/'));
    }

    /*
    * Страница post
    */
    public function showPost($post_url) {
        $post = $this->model->getPostByUrl($post_url);
        if (!$post) {
            header("HTTP/1.0 404 Not Found");
            $content = View::render('../app/views/errors/404.php', [
                'title' => '404'
            ]);
            
            require '../app/views/layout.php';
            return;
        }

       // print_r($post);
        $URL = rtrim(sprintf("%s/%s", $this->uri, $post['url']), '/').'.html';
    
        $render_params =[
            'post' => $post,
            'full_url' => $URL,
            'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'tags' => $post['tags'],
            'is_post' => true
        ];

        if (isset($post['image'])) {
            $render_params['post_image'] = sprintf("%s%s", $this->uri, $post['image']);
        }
        //используется в layout.php
        $content = View::render('../app/views/posts/show.php', $render_params);

        //используется в layout.php
        $structuredData = [
            'page_type' => 'post',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s%s", $this->uri, $post['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Страница Контакты
    */
    public function showKontakty() {
        $URL = rtrim(sprintf("%s/%s", $this->uri, 'page/kontakty'), '/').'.html';
    
        //используется в layout.php
        $content = View::render('../app/views/pages/kontakty.php', [
            //'post' => $page,
            'full_url' => $this->requestUrl,
            'url_id' => 'kontakty'
            //'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
            //'tags' => $tags,
            //'is_post' => false
        ]);

        //используется в layout.php
        $structuredData = [
            'page_type' => 'kontakty',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $this->requestUrl
            //'image' => sprintf("%s%s", $this->uri, $page['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Страница Карта сайта
    */
    public function showSitemap() {
        $posts = $this->model->getSitemapData();
        if (!$posts) {
            header("HTTP/1.0 404 Not Found");
            $content = View::render('../app/views/errors/404.php', [
                'title' => '404'
            ]);
            
            require '../app/views/layout.php';
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

        //используется в layout.php
        $content = View::render('../app/views/pages/sitemap.php', [
            'data' => $result,
            'full_url' => $this->requestUrl,
            'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
            //'tags' => $tags,
            'is_post' => false
        ]);

        //используется в layout.php
        $structuredData = [
            'page_type' => 'sitemap',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $this->requestUrl
            //'image' => sprintf("%s%s", $this->uri, $page['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Страница Тэги
    */
    public function showTagFilter() {
        //используется в layout.php
        $content = View::render('../app/views/posts/tegi.php', [
            'show_caption' => true,
            'full_url' => $this->requestUrl,
            'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
            //'tags' => $tags,
            'is_post' => false
        ]);

        //используется в layout.php
        $structuredData = [
            'page_type' => 'tegi',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $this->requestUrl
            //'image' => sprintf("%s%s", $this->uri, $page['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Страница page
    */
    public function showPage($page_url) {
        $page = $this->model->getPageByUrl($page_url);
        if (!$page) {
            header("HTTP/1.0 404 Not Found");
            $content = View::render('../app/views/errors/404.php', [
                'title' => '404'
            ]);
            
            require '../app/views/layout.php';
            return;
        }

        $URL = rtrim(sprintf("%s/%s", $this->uri, $page['url']), '/').'.html';
    
        //используется в layout.php
        $content = View::render('../app/views/posts/show.php', [
            'post' => $page,
            'full_url' => $URL,
            'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
            //'tags' => $tags,
            'is_post' => false
        ]);

        //используется в layout.php
        $structuredData = [
            'page_type' => 'post',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $URL
            //'image' => sprintf("%s%s", $this->uri, $page['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Главная страница (список постов)
    */
    public function index($page = 1) {
        $posts_per_page = Config::get('posts.posts_per_page');
        $total_posts = $this->model->countAllPosts(); // из предыдущих улучшений
        $total_pages = ceil($total_posts / $posts_per_page);
        $page = max(1, min((int)$page, $total_pages));

        $posts = $this->model->getAllPosts($posts_per_page, $page);

        // для генерации ссылки перехода на след/пред страницу < или >
        // для главной страницы передаем пустую строку, чтобы не создалась ссылка
        // с двумя слэшами //p ...
        $base_page_url = "";
        // Генерируем ссылки для умной пагинации
        $pagination_links = generateSmartPaginationLinks($page, $total_pages, $base_page_url);

        $URL = rtrim(sprintf("%s", $this->uri), '/');

        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => false,
            'url' => $URL,
            'show_read_next' => false,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_posts' => $total_posts,
                'posts_per_page' => $posts_per_page,
            ],
            'pagination_links' => $pagination_links,
            'base_page_url' => $base_page_url
        ]);

        // используется в layout.php
        $structuredData = [
            'page_type' => 'home',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
    }

    /*
    * Список постов раздела меню
    */
    public function showSection($cat_url, $show_link_next, $page = 1) {
        $posts_per_page = Config::get('posts.posts_per_page');
        $total_posts = $this->model->countAllPostsByCategory($cat_url); // из предыдущих улучшений
        $total_pages = ceil($total_posts / $posts_per_page);
        $page = max(1, min((int)$page, $total_pages));
        $posts = $this->model->getAllPostsByCategory($cat_url, $show_link_next, $posts_per_page, $page);

        $base_page_url = "/cat/{$cat_url}"; // для генерации ссылки перехода на след/пред страницу < или >
        // Генерируем ссылки для умной пагинации
        $pagination_links = generateSmartPaginationLinks($page, $total_pages);

        //print_r($posts);
        $URL = rtrim(sprintf("%s", $this->uri), '/');

        // используется в layout.php
        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => true,
            'caption' => 'Рубрика: ' . (!empty($posts) ? ($posts[0]['category_name'] ?? '') : ''),
            'caption_desc' => null,
            'url' => $URL,
            'show_read_next' => $show_link_next,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_posts' => $total_posts,
                'posts_per_page' => $posts_per_page,
            ],
            'pagination_links' => $pagination_links,
            'base_page_url' => $base_page_url
        ]);

        // используется в layout.php
        $structuredData = [
            'page_type' => 'home',
            'site_name' => Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
    }

    /*
    * Список постов по тэгу
    */
    public function showTag($tag_url, $page = 1) {
        $posts_per_page = Config::get('posts.posts_per_page');
        $total_posts = $this->model->countAllPostsByTag($tag_url); // из предыдущих улучшений
        $total_pages = ceil($total_posts / $posts_per_page);
        $page = max(1, min((int)$page, $total_pages));
        $posts = $this->model->getAllPostsByTag($tag_url, $posts_per_page, $page);

        $base_page_url = "/tag/{$tag_url}"; // для генерации ссылки перехода на след/пред страницу < или >
        // Генерируем ссылки для умной пагинации
        $pagination_links = generateSmartPaginationLinks($page, $total_pages);

        //print_r($posts);
        $URL = rtrim(sprintf("%s", $this->uri), '/');
        $caption = 'Тэг: ' . (!empty($posts) ? ($posts[0]['tag_name'] ?? '') : '');

        // используется в layout.php
        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => true,
            'caption' => $caption,
            'caption_desc' => null,
            'url' => $URL,
            'show_read_next' => false,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_posts' => $total_posts,
                'posts_per_page' => $posts_per_page,
            ],
            'pagination_links' => $pagination_links,
            'base_page_url' => $base_page_url
        ]);

        // используется в layout.php
        $structuredData = [
            'page_type' => 'home',
            'site_name' => "$caption | " . Config::get('global.SITE_NAME'),
            'keywords' => Config::get('global.SITE_KEYWORDS'),
            'description' => Config::get('global.SITE_DESCRIPTION'),
            'url' => $this->requestUrl,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
    }


    
}