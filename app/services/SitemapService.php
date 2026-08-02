<?php

// app/services/SitemapService.php

/**
 * Сервис для работы с данными карты сайта и LLMS
 * 
 * Обрабатывает данные, полученные из SitemapModel, группирует их по типам
 * и категориям, подготавливая для вывода в sitemap.xml или llms.txt
 */
class SitemapService
{
    /**
     * @var SitemapModel Объект модели для работы с данными карты сайта.
     */
    private SitemapModel $sitemapModel;

    /**
     * Сервис для получения сео настроек
     */
    private SettingsService $settingsService;

     /**
     * Конструктор сервиса
     * 
     * @param SitemapModel $sitemapModel Модель для получения данных
     */
    public function __construct(SitemapModel $sitemapModel, SettingsService $settingsService)
    {
        $this->sitemapModel = $sitemapModel;
        $this->settingsService = $settingsService;
    }

    /**
     * Получает и группирует данные для карты сайта или LLMS
     * 
     * Возвращает структурированный массив, разделенный на посты и страницы.
     * Посты группируются по категориям, страницы собираются в отдельный раздел.
     * 
     * @param int $llmsDescriptionLength Длина описания при обрезании content (в символах)
     * @param bool $withDescription Флаг, показывающий, включать или нет поле description в результат
     * @param bool $withCatDesription Флаг, показывающий, включать или нет поле description рубрик в результат
     * 
     * @return array<string, mixed> Структурированные данные:
     *                               - post: массив категорий с постами
     *                               - page: массив страниц
     * 
     * @throws HttpException Если данные не найдены (HTTP 404)
     */
    public function getSitemapData__(int $llmsDescriptionLength,
        bool $withDescription = false, bool $withCatDesription = false): array
    {
        $posts = $this->sitemapModel->getSitemapData($llmsDescriptionLength);
        if (!$posts) {
            throw new HttpException('Страница не найдена', 404);
        }

        $result = [
            'post' => [],
            'page' => [
                'pages' => []
            ],
            'last-modified' => null
        ];
        
        foreach ($posts as $row) {
            // Обновляем последнюю дату
            if ($lastModified === null || $row['updated_at'] > $lastModified) {
                $lastModified = $row['updated_at'];
            }

            if ($row['type'] === 'post') {
                // Это обычный пост с категорией
                $categoryUrl = $row['category_url'];
        
                // Вставляем категорию
                if (!isset($result['post'][$categoryUrl])) {
                    $result['post'][$categoryUrl] = [
                        'name' => $row['category_name'],
                        'url' => $row['category_url'],
                        'posts' => []
                    ];
                }
        
                // Вставляем пост
                $result['post'][$categoryUrl]['posts'][] = $this->addNewRow($row, $withDescription);
            } elseif ($row['type'] === 'page') {
                // Вставляем страницу
                $result['page']['pages'][] = $this->addNewRow($row, $withDescription);
            }
        }

        // Сохраняем последнюю дату в формате ISO 8601
        $result['last-modified'] = $lastModified 
            ? (new \DateTime($lastModified))->format('Y-m-d') 
            : null;

        return $result;
    }



    public function getSitemapData(
        int $llmsDescriptionLength,
        bool $withDescription = false,
        bool $withCatDescription = false
    ): array {
        $posts = $this->sitemapModel->getSitemapData($llmsDescriptionLength);
        
        if (empty($posts)) {
            throw new HttpException('Страница не найдена', 404);
        }

        $result = [
            'post' => [],
            'page' => [
                'pages' => []
            ],
            'last-modified' => null
        ];
        
        $lastModified = null;
        $categoryUrls = []; // Собираем URL категорий для массовой загрузки описаний
        
        foreach ($posts as $row) {
            // Обновляем последнюю дату
            if ($lastModified === null || $row['updated_at'] > $lastModified) {
                $lastModified = $row['updated_at'];
            }

            if ($row['type'] === 'post') {
                $categoryUrl = $row['category_url'];
                
                // Сохраняем URL категории, если нужны описания
                if ($withCatDescription && !in_array($categoryUrl, $categoryUrls)) {
                    $categoryUrls[] = $categoryUrl;
                }
                
                // Вставляем категорию
                if (!isset($result['post'][$categoryUrl])) {
                    $result['post'][$categoryUrl] = [
                        'name' => $row['category_name'],
                        'url' => $row['category_url'],
                        'posts' => []
                    ];
                }
                
                // Вставляем пост
                $result['post'][$categoryUrl]['posts'][] = $this->addNewRow($row, $withDescription);
                
            } elseif ($row['type'] === 'page') {
                // Вставляем страницу
                $result['page']['pages'][] = $this->addNewRow($row, $withDescription);
            }
        }

        // Если нужны описания категорий — загружаем их
        if ($withCatDescription && !empty($categoryUrls)) {
            $categoryDescriptions = $this->loadCategoryDescriptions($categoryUrls);
            
            // Добавляем описания в каждую категорию
            foreach ($result['post'] as $categoryUrl => &$categoryData) {
                $key = 'cat_' . $categoryUrl . '_caption_desc';
                $categoryData['description'] = get_clean_description($categoryDescriptions[$key]['value'] ?? null);
            }
            unset($categoryData); // разрываем ссылку
        }

        // Сохраняем последнюю дату
        $result['last-modified'] = $lastModified 
            ? (new \DateTime($lastModified))->format('Y-m-d') 
            : null;

        return $result;
    }

    /**
     * Загружает описания для категорий.
     *
     * @param array $categoryUrls Список URL категорий.
     * @return array<string, string|null> Ассоциативный массив [ключ => описание].
     */
    private function loadCategoryDescriptions(array $categoryUrls): array
    {
        // Формируем массив ключей для массовой загрузки
        $keys = array_map(function($url) {
            return 'cat_' . $url . '_caption_desc';
        }, $categoryUrls);
        
        // Загружаем настройки
        $seoSettings = $this->settingsService->getMassSeoSettings($keys, $categoryUrls);
        
        // Возвращаем как есть (или можно преобразовать, но ключи уже правильные)
        return $seoSettings;
    }


    /**
     * Формирует массив данных для одного поста или страницы
     * 
     * Извлекает из строки результата запроса необходимые поля
     * и формирует структурированный массив.
     * 
     * @param array<string, mixed> $curRow Строка данных из модели
     * @param bool $withDescription Флаг, включающий поле description
     * 
     * @return array<string, mixed> Ассоциативный массив с полями:
     *                              - title: заголовок поста/страницы
     *                              - url: URL поста/страницы
     *                              - description: описание (если $withDescription = true)
     * 
     * @see SitemapModel::getSitemapData()
     */
    private function addNewRow(array $curRow, $withDescription): array
    {
        $newPost = [
            'title' => $curRow['post_title'],
            'url' => $curRow['post_url']
        ];

        if ($withDescription)
        {
            $newPost['description'] = get_clean_description($curRow['description']);
        }

        return $newPost;
    }
}