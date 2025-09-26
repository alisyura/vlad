<?php

// app/controllers/SitemapController.php

/**
 * Class SitemapController
 *
 * Контроллер для работы с картой сайта (sitemap) и соответствующими XML-файлами.
 */
class SitemapController {
    use ShowClientErrorViewTrait;
    
    /**
     * @var string Базовый URI приложения.
     */
    private string $uri;

    /**
     * @var int Максимальное количество URL в одном файле карты сайта.
     */
    private int $maxUrls;
    
    /**
     * @var Request Объект HTTP запроса.
     */
    private Request $request;
    
    /**
     * @var ViewAdmin Объект представления для рендеринга.
     */
    private ViewAdmin $view;

    /**
     * @var SitemapModel Объект модели для работы с данными карты сайта.
     */
    private SitemapModel $model;

     /**
     * Конструктор класса SitemapController.
     *
     * @param Request $request Объект HTTP запроса, внедряемый через Dependency Injection.
     * @param ViewAdmin $view Объект представления, внедряемый через Dependency Injection.
     * @param SitemapModel $sitemapModel Объект модели, внедряемый через Dependency Injection.
     */
    public function __construct(Request $request, ViewAdmin $view, SitemapModel $sitemapModel) {
        $this->request = $request;
        $this->view = $view;
        $this->model = $sitemapModel;

        $this->uri = $request->getBaseUrl();
        $this->maxUrls = Config::get('posts.max_urls_in_sitemap');
    }

    /**
     * Отображает HTML-страницу карты сайта.
     *
     * Метод получает все данные о постах и страницах и передает их в представление
     * для отображения в виде читабельной карты сайта.
     *
     * @return void
     */
    public function showSitemap():void {
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
                    'robots' => 'index, follow',
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

    /**
     * Генерирует и выводит корневой файл карты сайта (sitemapindex.xml).
     *
     * Метод вычисляет количество частей карты сайта для постов и страниц,
     * а затем передает эти данные в соответствующее представление.
     *
     * @return void
     */
    public function generateSitemapIndexXml(): void
    {
        try {
            // Чанки для постов
            $total_posts = $this->model->getPostsCount('post');
            $chunks_posts = ceil($total_posts / $this->maxUrls);

            // Чанки для страниц
            $total_pages = $this->model->getPostsCount('page');
            $chunks_pages = ceil($total_pages / $this->maxUrls);

            $contentData = [
                'chunks_posts' => $chunks_posts,
                'chunks_pages' => $chunks_pages,
                'url' => $this->uri
            ];
            echo $this->view->render(
                'sitemap/sitemap-index.xml.php', 
                $contentData, 
                ['Content-Type: application/xml; charset=utf-8']);
        } catch (Throwable $e) {
            Logger::error('Error generating sitemap XML: ', [$e->getTraceAsString()]);

            $contentData = [
                'title' => 'Произошла внутренняя ошибка сервера.'
            ];
            echo $this->view->render(
                'errors/error_xml.php', 
                $contentData, 
                ['Content-Type: application/xml; charset=utf-8'], 
                500, true);
        }
    }

    /**
     * Генерирует и выводит часть файла карты сайта в формате XML.
     *
     * Метод получает данные постов или страниц, разделяя их на части
     * для создания отдельных файлов карты сайта.
     *
     * @param string $type Тип контента ('post' или 'page').
     * @param int $page Номер страницы (части) карты сайта.
     */
    public function generateSitemapPartXml(string $type, int $page): void
    {
        try {
            $offset = ($page - 1) * $this->maxUrls;
            $items = $this->model->getPostsByOffsetNum($offset, $type, $this->maxUrls);

            $render_parts = [
                'url' => $this->uri,
                'posts' => [],
                'pages' => []
            ];

            switch ($type) {
                case 'post':
                    $render_parts['posts'] = $items;
                    $render_parts['posts_priority'] = "0.6";
                    $render_parts['changefreq_posts'] = 'weekly';
                    break;
                case 'page':
                    $render_parts['pages'] = $items;
                    $render_parts['pages_priority'] = "0.5";
                    $render_parts['changefreq_pages'] = 'monthly';
                    break;
            }

            echo $this->view->render(
                'sitemap/sitemap-part.xml.php',
                $render_parts,
                ['Content-Type: application/xml; charset=utf-8']
            );
        } catch (Throwable $e) {
            Logger::error("Error generating sitemap part XML type={$type}, page={$page}: ", 
                [$e->getTraceAsString()]);

            $contentData = [
                'title' => 'Произошла внутренняя ошибка сервера.'
            ];
            echo $this->view->render(
                'errors/error_xml.php', 
                $contentData, 
                ['Content-Type: application/xml; charset=utf-8'], 
                500, true);
        }
    }
}