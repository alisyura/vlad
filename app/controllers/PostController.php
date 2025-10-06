<?php

class PostController {
    use ShowClientErrorViewTrait;

    /**
     * Экземпляр модели
     */
    private PostModel $model;

    private Request $request;
    private ViewAdmin $view;

    public function __construct(Request $request, ViewAdmin $view, PostModel $postModel) {
        $this->model = $postModel;
        $this->request = $request;
        $this->view = $view;
    }

    /*
    * Страница post
    */
    public function showPost($post_url) {
        try {
            $post = $this->model->getPostByUrl($post_url);
            if (!$post) {
                $this->showErrorView('Пост не найден', '', 404);
                return;
            }

            $baseUrl= $this->request->getBaseUrl();
            $URL = sprintf("%s/%s", $baseUrl, $post['url']).'.html';
        
            $render_params =[
                'post' => $post,
                'full_url' => $URL,
                'tags_baseUrl' => sprintf("%s/tag/", $baseUrl),
                'is_post' => true,
                'export' => [
                    'page_type' => 'post',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'title' => $post['meta_title'] . ' | ' . Config::get('global.SITE_NAME'),
                    'keywords' => $post['meta_keywords'],
                    'description' => $post['meta_description'],
                    'url' => $baseUrl,
                    'image' => sprintf("%s%s", $baseUrl, asset('pic/logo.png')),
                    'robots' => 'index, follow',
                    'styles' => [
                        'detail.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            if (isset($post['image'])) {
                $render_params['post_image'] = sprintf("%s%s", $baseUrl, $post['image']);
            }

            $this->view->renderClient('posts/show.php', $render_params);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    /*
    * Страница page
    */
    public function showPage($page_url) {
        try {
            $page = $this->model->getPageByUrl($page_url);
            if (!$page) {
                $this->showErrorView('Страница не найдена', '', 404);
                return;
            }

            $baseUrl= $this->request->getBaseUrl();
            $URL = sprintf("%s/%s", $baseUrl, $page['url']).'.html';
        
            $contentData = [
                'post' => $page,
                'full_url' => $URL,
                'tags_baseUrl' => sprintf("%s/tag/", $baseUrl),
                'is_post' => false,
                'export' => [
                    'page_type' => 'post',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'title' => $page['meta_title'] . ' | ' . Config::get('global.SITE_NAME'),
                    'keywords' => $page['meta_keywords'],
                    'description' => $page['meta_description'],
                    'url' => $baseUrl,
                    'image' => sprintf("%s%s", $baseUrl, asset('pic/logo.png')),
                    'robots' => 'index, follow',
                    'styles' => [
                        'detail.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            $this->view->renderClient('posts/show.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showPage: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    /*
    * Главная страница (список постов)
    */
    public function index($page = 1) {
        try {
            $posts_per_page = Config::get('posts.posts_per_page');
            $total_posts = $this->model->countAllPosts();

            // для генерации ссылки перехода на след/пред страницу < или >
            // для главной страницы передаем пустую строку, чтобы не создалась ссылка
            // с двумя слэшами //p ...
            $base_page_url = "";

            // Генерируем массив ссылок для умной пагинации
            $ps = new PaginationService();
            $paginParams = $ps->calculatePaginationParams($posts_per_page, $page, 
                $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPosts($posts_per_page, $page);

            $baseUrl = $this->request->getBaseUrl();
            
            $contentData = [
                'posts' => $posts,
                'show_caption' => false,
                'url' => $baseUrl,
                'show_read_next' => false,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'posts_per_page' => $posts_per_page,
                ],
                'pagination_links' => $pagination_links,
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'title' => Config::get('global.SITE_NAME'),
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $baseUrl,
                    'image' => $baseUrl . asset('pic/logo.png'),
                    'posts' => $posts,
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'list.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            $this->view->renderClient('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    /*
    * Список постов из раздела меню
    */
    public function showBySection($cat_url, $show_link_next, $page = 1) {
        try {
            $posts_per_page = Config::get('posts.posts_per_page');
            $total_posts = $this->model->countAllPostsByCategory($cat_url);
            
            // для генерации ссылки перехода на след/пред страницу < или >
            $base_page_url = "/cat/{$cat_url}";

            // Генерируем массив ссылок для умной пагинации
            $ps = new PaginationService();
            $paginParams = $ps->calculatePaginationParams($posts_per_page, $page, 
                $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPostsByCategory($cat_url, $show_link_next, $posts_per_page, $page);

            $baseUrl = $this->request->getBaseUrl();

            // здесь категория одна у всех постов, поэтому берем из 1го элемента
            $category_name = (!empty($posts) ? ($posts[0]['category_name'] ?? '') : '');
            $contentData = [
                'posts' => $posts,
                'show_caption' => true,
                'caption' => 'Рубрика: ' . $category_name,
                'caption_desc' => null,
                'url' => $baseUrl,
                'show_read_next' => $show_link_next,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'posts_per_page' => $posts_per_page,
                ],
                'pagination_links' => $pagination_links,
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'title' => "$category_name | " . Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $baseUrl,
                    'image' => sprintf("%s/assets/pic/logo.png", $baseUrl),
                    'posts' => $posts,
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'list.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            $this->view->renderClient('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showSection: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    /*
    * Список постов по тэгу
    */
    public function showByTag($tag_url, $page = 1) {
        try {
            $posts_per_page = Config::get('posts.posts_per_page');
            $total_posts = $this->model->countAllPostsByTag($tag_url);
            
            // для генерации ссылки перехода на след/пред страницу < или >
            $base_page_url = "/tag/{$tag_url}";

            // Генерируем массив ссылок для умной пагинации
            $ps = new PaginationService();
            $paginParams = $ps->calculatePaginationParams($posts_per_page, $page, 
                $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPostsByTag($tag_url, $posts_per_page, $page);

            $baseUrl = $this->request->getBaseUrl();
            $caption = 'Тэг: ' . (!empty($posts) ? ($posts[0]['tag_name'] ?? '') : '');

            $contentData = [
                'posts' => $posts,
                'show_caption' => true,
                'caption' => $caption,
                'caption_desc' => null,
                'url' => $baseUrl,
                'show_read_next' => false,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'posts_per_page' => $posts_per_page,
                ],
                'pagination_links' => $pagination_links,
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'title' => "$caption | " . Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->getRequestUrl(),
                    'image' => sprintf("%s/assets/pic/logo.png", $baseUrl),
                    'posts' => $posts,
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'list.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            $this->view->renderClient('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }
}