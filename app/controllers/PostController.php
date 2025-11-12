<?php

class PostController extends BaseController {
    /**
     * Экземпляр модели
     */
    private PostModelClient $model;

    /**
     * Сервис вычисления параметров пагинации
     */
    private PaginationService $paginService;

    /**
     * Конструктор класса PostController.
     *
     * @param Request $request Объект HTTP запроса, внедряемый через Dependency Injection.
     * @param View $view Объект представления, внедряемый через Dependency Injection.
     * @param PostModelClient $sitemapModel Объект модели, внедряемый через Dependency Injection.
     * @param ResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     * @param PaginationService $paginService Сервис для вычисления параметров пагинации, внедряется через Dependency Injection..
     */
    public function __construct(Request $request, View $view, PostModelClient $postModel,
        ResponseFactory $responseFactory, PaginationService $paginService)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->model = $postModel;
        $this->paginService = $paginService;
    }

    /*
    * Страница post
    */
    public function showPost($post_url): Response {
        try {
            $post = $this->model->getPostByUrl($post_url);
            if (!$post) {
                throw new HttpException('Пост не найден', 404);
            }

            $baseUrl= $this->getRequest()->getBaseUrl();
            $URL = sprintf("%s/%s", $baseUrl, $post['url']).'.html';
        
            $renderParams =[
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
                $renderParams['post_image'] = sprintf("%s%s", $baseUrl, $post['image']);
            }

            return $this->renderHtml('posts/show.php', $renderParams);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Logger::error("Error in showPost: ", ['post_url' => $post_url], $e);
            throw new HttpException('Ошибка при открытии поста', 500, $e);
        }
    }

    /*
    * Страница page
    */
    public function showPage($page_url): Response {
        try {
            $page = $this->model->getPageByUrl($page_url);
            if (!$page) {
                throw new HttpException('Страница не найдена', 404);
            }

            $baseUrl= $this->getRequest()->getBaseUrl();
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

            return $this->renderHtml('posts/show.php', $contentData);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Logger::error("Error in showPage: ", ['page_url' => $page_url], $e);
            throw new HttpException('Ошибка при открытии страницы', 500, $e);
        }
    }

    /*
    * Главная страница (список постов)
    */
    public function index($page = 1): Response {
        try {
            $posts_per_page = Config::get('posts.posts_per_page');
            $total_posts = $this->model->countAllPosts();

            // для генерации ссылки перехода на след/пред страницу < или >
            // для главной страницы передаем пустую строку, чтобы не создалась ссылка
            // с двумя слэшами //p ...
            $base_page_url = "";

            // Генерируем массив ссылок для умной пагинации
            $paginParams = $this->paginService->calculatePaginationParams($posts_per_page, $page, 
                $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPosts($posts_per_page, $page);

            $baseUrl = $this->getRequest()->getBaseUrl();
            
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
                    'robots' => 'index, follow',
                    'styles' => [
                        'list.css'
                    ],
                    'jss' => [
                    ]
                ]
            ];

            return $this->renderHtml('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in listPosts (index): ", ['page' => $page], $e);
            throw new HttpException('Ошибка получения списка постов', 500, $e);
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
            $paginParams = $this->paginService->calculatePaginationParams($posts_per_page, 
                $page, $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPostsByCategory($cat_url, $show_link_next, $posts_per_page, $page);

            $baseUrl = $this->getRequest()->getBaseUrl();

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

          return $this->renderHtml('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showBySection: ", ['cat_url' => $cat_url, 'show_link_next' => $show_link_next, 'page' => $page], $e);
            throw new HttpException('Ошибка получения списка постов по разделу', 500, $e);
        }
    }

    /*
    * Список постов по тэгу
    */
    public function showByTag($tag_url, $page = 1): Response {
        try {
            $posts_per_page = Config::get('posts.posts_per_page');
            $total_posts = $this->model->countAllPostsByTag($tag_url);
            
            // для генерации ссылки перехода на след/пред страницу < или >
            $base_page_url = "/tag/{$tag_url}";

            // Генерируем массив ссылок для умной пагинации
            $paginParams = $this->paginService->calculatePaginationParams($posts_per_page, 
                $page, $total_posts, $base_page_url);
            
            ['totalPages' => $total_pages, 
                    'paginationLinks' => $pagination_links] = $paginParams;

            $posts = $this->model->getAllPostsByTag($tag_url, $posts_per_page, $page);

            $baseUrl = $this->getRequest()->getBaseUrl();
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
                    'url' => $this->getRequest()->getRequestUrl(),
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

             return $this->renderHtml('posts/index.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showByTag: ", ['tag_url' => $tag_url, 'page' => $page], $e);
            throw new HttpException('Ошибка получения списка постов по тэгу', 500, $e);
        }
    }
}