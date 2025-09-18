<?php

class PostController {
    use ShowClientErrorViewTrait;

    /**
     * Экземпляр модели
     */
    private $model;
    /**
     * Адрес сайта (схема и домен)
     */
    private $uri;
    /**
     * Url с которого пришел запрос
     */
    private $requestUrl;
    private Request $request;
    private ViewAdmin $view;

    public function __construct(Request $request, ViewAdmin $view, PostModel $postModel) {
        $this->model = $postModel;
        $this->request = $request;
        $this->view = $view;
        $this->uri = $this->request->getBaseUrl();
        $this->requestUrl = $this->request->getRequestUrl();
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

            $URL = rtrim(sprintf("%s/%s", $this->uri, $post['url']), '/').'.html';
        
            $render_params =[
                'post' => $post,
                'full_url' => $URL,
                'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'tags' => $post['tags'],
                'is_post' => true,
                'export' => [
                    'page_type' => 'post',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $URL,
                    'image' => sprintf("%s%s", $this->uri, $post['image']),
                    'styles' => [
                        'detail.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            if (isset($post['image'])) {
                $render_params['post_image'] = sprintf("%s%s", $this->uri, $post['image']);
            }

            $this->view->renderClient('posts/show.php', $render_params);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }

    /*
    * Страница Контакты
    */
    public function showKontakty() {
        try {
            // $URL = rtrim(sprintf("%s/%s", $this->uri, 'page/kontakty'), '/').'.html';
        
            $contentData = [
                //'post' => $page,
                'full_url' => $this->requestUrl,
                'url_id' => 'kontakty',
                //'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                //'is_post' => false
                'export' => [
                    'page_type' => 'kontakty',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->requestUrl,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
                    'styles' => [
                        'kontakty.css'
                    ],
                    'jss' => [
                        'kontakty.js'
                    ]
                ]
            ];

            $this->view->renderClient('pages/kontakty.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showKontakty: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
        
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
                'full_url' => $this->requestUrl,
                'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'sitemap',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->requestUrl,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
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

    /*
    * Страница Тэги
    */
    public function showTagFilter() {
        try {
            //используется в layout.php
            $contentData = [ //View::render('../app/views/posts/tegi.php', [
                'show_caption' => true,
                'full_url' => $this->requestUrl,
                'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->requestUrl,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
                    'styles' => [
                        'tegi.css'
                    ],
                    'jss' => [
                        'tegi.js'
                    ]
                ]
            ];

            $this->view->renderClient('posts/tegi.php', $contentData);

            //используется в layout.php
            // $structuredData = [
            //     'page_type' => 'tegi',
            //     'site_name' => Config::get('global.SITE_NAME'),
            //     'keywords' => Config::get('global.SITE_KEYWORDS'),
            //     'description' => Config::get('global.SITE_DESCRIPTION'),
            //     'url' => $this->requestUrl
            //     //'image' => sprintf("%s%s", $this->uri, $page['image'])
            // ];
            
            // require '../app/views/layout.php';
        } catch (Throwable $e) {
            Logger::error("Error in showTagFilter: " . $e->getTraceAsString());
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

            $URL = rtrim(sprintf("%s/%s", $this->uri, $page['url']), '/').'.html';
        
            $contentData = [
                'post' => $page,
                'full_url' => $URL,
                'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'post',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $URL,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
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

            $URL = rtrim(sprintf("%s", $this->uri), '/');
            
            $contentData = [
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
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $URL,
                    'image' => sprintf("%s/assets/pic/logo.png", $URL),
                    'posts' => $posts,
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
    * Список постов раздела меню
    */
    public function showSection($cat_url, $show_link_next, $page = 1) {
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

            $URL = rtrim(sprintf("%s", $this->uri), '/');

            $contentData = [
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
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $URL,
                    'image' => sprintf("%s/assets/pic/logo.png", $URL),
                    'posts' => $posts,
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
    public function showTag($tag_url, $page = 1) {
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

            $URL = rtrim(sprintf("%s", $this->uri), '/');
            $caption = 'Тэг: ' . (!empty($posts) ? ($posts[0]['tag_name'] ?? '') : '');

            $contentData = [
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
                'base_page_url' => $base_page_url,
                'export' => [
                    'page_type' => 'home',
                    'site_name' => "$caption | " . Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->requestUrl,
                    'image' => sprintf("%s/assets/pic/logo.png", $URL),
                    'posts' => $posts,
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