<?php

require_once __DIR__.'/../models/PostModel.php';
require_once __DIR__.'/../core/View.php';

class PostController {
    private $model;
    private $uri;

    public function __construct() {
        $this->model = new PostModel();
        $this->uri = sprintf("%s://%s", $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
    }

    /*
    * Главная страница (список постов)
    */
    public function index($page = 1) {
        $posts = $this->model->getAllPosts($page);
        $posts_per_page = Config::getPostsCfg('posts_per_page');

        // Получаем общее количество опубликованных постов
        $total_posts = $this->model->countAllPosts();

        // Генерируем ссылки пагинации
        $pagination_links = generatePaginationLinks(
            $page,
            $total_posts,
            $posts_per_page,
            '/' // базовый URL
        );

        $URL = rtrim(sprintf("%s", $this->uri), '/');

        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => false,
            'url' => $URL,
            'show_read_next' => false,
            'pagination' => [
                'current_page' => $page,
                'posts_per_page' => $posts_per_page,
                'total_posts' => $total_posts,
            ],
            'pagination_links' => $pagination_links
        ]);

        $structuredData = [
            'page_type' => 'home',
            'site_name' => Config::getGlobalCfg('SITE_NAME'),
            'keywords' => Config::getGlobalCfg('SITE_KEYWORDS'),
            'description' => Config::getGlobalCfg('SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
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
        //print_r($render_params);
        $content = View::render('../app/views/posts/show.php', $render_params);

        $structuredData = [
            'page_type' => 'post',
            'site_name' => Config::getGlobalCfg('SITE_NAME'),
            'keywords' => Config::getGlobalCfg('SITE_KEYWORDS'),
            'description' => Config::getGlobalCfg('SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s%s", $this->uri, $post['image'])
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
    
        $content = View::render('../app/views/posts/show.php', [
            'post' => $page,
            'full_url' => $URL,
            'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
            //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
            //'tags' => $tags,
            'is_post' => false
        ]);

        $structuredData = [
            'page_type' => 'post',
            'site_name' => Config::getGlobalCfg('SITE_NAME'),
            'keywords' => Config::getGlobalCfg('SITE_KEYWORDS'),
            'description' => Config::getGlobalCfg('SITE_DESCRIPTION'),
            'url' => $URL
            //'image' => sprintf("%s%s", $this->uri, $page['image'])
        ];
        
        require '../app/views/layout.php';
    }

    /*
    * Список постов раздела меню
    */
    public function showSection($cat_url, $show_link_next) {
        $posts = $this->model->getAllPostsBySection($cat_url, $show_link_next);

        //print_r($posts);
        $URL = rtrim(sprintf("%s", $this->uri), '/');

        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => true,
            'url' => $URL,
            'show_read_next' => $show_link_next
        ]);

        $structuredData = [
            'page_type' => 'home',
            'site_name' => Config::getGlobalCfg('SITE_NAME'),
            'keywords' => Config::getGlobalCfg('SITE_KEYWORDS'),
            'description' => Config::getGlobalCfg('SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
    }

    /*
    * Список постов по тэгу
    */
    public function showTag($tag_url) {
        $posts = $this->model->getAllPostsByTag($tag_url);

        //print_r($posts);
        $URL = rtrim(sprintf("%s", $this->uri), '/');

        $content = View::render('../app/views/posts/index.php', [
            'posts' => $posts,
            'show_caption' => true,
            'url' => $URL,
            'show_read_next' => $show_link_next
        ]);

        $structuredData = [
            'page_type' => 'home',
            'site_name' => Config::getGlobalCfg('SITE_NAME'),
            'keywords' => Config::getGlobalCfg('SITE_KEYWORDS'),
            'description' => Config::getGlobalCfg('SITE_DESCRIPTION'),
            'url' => $URL,
            'image' => sprintf("%s/assets/pic/logo.png", $URL),
            'posts' => $posts
        ];

        require '../app/views/layout.php';
    }
}