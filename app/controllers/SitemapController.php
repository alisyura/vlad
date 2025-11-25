<?php

// app/controllers/SitemapController.php

/**
 * Class SitemapController
 *
 * Контроллер для работы с картой сайта (sitemap) и соответствующими XML-файлами.
 */
class SitemapController extends BaseController {
    /**
     * @var int Максимальное количество URL в одном файле карты сайта.
     */
    private int $maxUrls;
    
    /**
     * @var SitemapModel Объект модели для работы с данными карты сайта.
     */
    private SitemapModel $model;

    /**
     * Модель для получения сео настроек
     */
    private SettingsModel $settingModel;

     /**
     * Конструктор класса SitemapController.
     *
     * @param Request $request Объект HTTP запроса, внедряемый через Dependency Injection.
     * @param View $view Объект представления, внедряемый через Dependency Injection.
     * @param SitemapModel $sitemapModel Объект модели, внедряемый через Dependency Injection.
     * @param ApplicationResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, View $view, SitemapModel $sitemapModel,
        ApplicationResponseFactory $responseFactory, SettingsModel $settingModel) 
    {
        parent::__construct($request, $view, $responseFactory);
        $this->model = $sitemapModel;
        $this->settingModel = $settingModel;

        $this->maxUrls = Config::get('posts.max_urls_in_sitemap');
    }

    protected function getResponseFactory(): ApplicationResponseFactory {
        return parent::getResponseFactory();
    }

    /**
     * Отображает HTML-страницу карты сайта.
     *
     * Метод получает все данные о постах и страницах и передает их в представление
     * для отображения в виде читабельной карты сайта.
     *
     * @return void
     */
    public function showSitemap(): Response {
        try {
            $posts = $this->model->getSitemapData();
            if (!$posts) {
                throw new HttpException('Страница не найдена', 404);
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

            $URL = $this->getRequest()->getBaseUrl();

            $seoSettings = $this->settingModel->getMassSeoSettings([
                'index_page_title',
                'index_page_description',
                'index_page_keywords']);

            $contentData = [
                'data' => $result,
                'full_url' => $this->getRequest()->getRequestUrl(),
                'is_post' => false,
                'export' => [
                    'page_type' => 'sitemap',
                    'title' => 'Карта сайта | ' . $seoSettings['index_page_title']['value'],
                    'site_name' => $seoSettings['index_page_title']['value'],
                    'keywords' => $seoSettings['index_page_keywords']['value'],
                    'description' => $seoSettings['index_page_description']['value'],
                    'url' => $URL,
                    'image' => $URL . asset('pic/logo.png'),
                    'robots' => 'index, follow',
                    'styles' => [
                        'sitemap.css'
                    ],
                    'jss' => [
                        'sitemap.js'
                    ]
                ]
            ];

            return $this->renderHtml('pages/sitemap.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showSitemap: ", [], $e);
            throw new HttpException('Ошибка получения списка постов для карты сайта', 500, $e);
        }
    }

    /**
     * Генерирует и выводит корневой файл карты сайта (sitemapindex.xml).
     *
     * Метод вычисляет количество частей карты сайта для постов и страниц,
     * а затем передает эти данные в соответствующее представление.
     *
     * @return Response
     */
    public function generateSitemapIndexXml(): Response
    {
        try {
            // Чанки для постов
            $total_posts = $this->model->getPostsCount('post');
            $chunks_posts = ceil($total_posts / $this->maxUrls);

            // Чанки для страниц
            $total_pages = $this->model->getPostsCount('page');
            $chunks_pages = ceil($total_pages / $this->maxUrls);

            return $this->getResponseFactory()->createSitemapIndexResponse(
                $chunks_posts, 
                $chunks_pages, 
                $this->getRequest()->getBaseUrl()
            );
        } catch (Throwable $e) {
            Logger::error('Error generating sitemap XML: ', [],  $e);

            throw new HttpException('Произошла внутренняя ошибка сервера.', 500, $e, HttpException::XML_RESPONSE);
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
     * 
     * @return Response
     */
    public function generateSitemapPartXml(string $type, int $page): Response
    {
        try {
            $offset = ($page - 1) * $this->maxUrls;
            $items = $this->model->getPostsByOffsetNum($offset, $type, $this->maxUrls);

            $render_parts = [
                'url' => $this->getRequest()->getBaseUrl(),
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

            return $this->getResponseFactory()->createSitemapPartResponse(
                $render_parts['url'],
                $render_parts['posts'],
                $render_parts['pages'],
                $render_parts['changefreq_posts'] ?? 'weekly',
                $render_parts['posts_priority'] ?? '0.6',
                $render_parts['changefreq_pages'] ?? 'monthly',
                $render_parts['pages_priority'] ?? '0.5'
            );
        } catch (Throwable $e) {
            Logger::error("Error generating sitemap part XML: ", 
                ['type' => $type, 'page' => $page], $e);

            throw new HttpException('Произошла внутренняя ошибка сервера.', 500, $e, HttpException::XML_RESPONSE);
        }
    }
}