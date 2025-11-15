<?php 

// app/http/responses/SitemapPartResponse.php

class SitemapPartResponse extends XmlResponse
{
    protected const XML_ROOT_NODE = 'urlset'; // Корневой элемент

    /**
     * Конструктор SitemapPartResponse.
     * @param string $url Базовый URL.
     * @param array $posts Массив данных о постах.
     * @param array $pages Массив данных о страницах.
     * @param string $changefreq_posts Частота изменения постов.
     * @param string $posts_priority Приоритет постов.
     * @param string $changefreq_pages Частота изменения страниц.
     * @param string $pages_priority Приоритет страниц.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     */
    public function __construct(
        string $url,
        array $posts,
        array $pages,
        string $changefreq_posts,
        string $posts_priority,
        string $changefreq_pages,
        string $pages_priority,
        int $statusCode = 200, 
        array $headers = []
    ) {
        $xmlContent = $this->generateSitemapPartXml(
            $url,
            $posts,
            $pages,
            $changefreq_posts,
            $posts_priority,
            $changefreq_pages,
            $pages_priority
        );
        
        // Передаем готовую XML-строку в родительский конструктор XmlResponse
        parent::__construct($xmlContent, $statusCode, $headers); 
    }

    /**
     * Генерирует XML-строку части карты сайта (<urlset>) с помощью SimpleXMLElement.
     */
    private function generateSitemapPartXml(
        string $url,
        array $posts,
        array $pages,
        string $changefreq_posts,
        string $posts_priority,
        string $changefreq_pages,
        string $pages_priority
    ): string 
    {
        $namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        
        // Создаем корневой элемент <urlset>
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><urlset/>', 
            0, 
            false, 
            $namespace
        );

        // Добавляем атрибуты пространств имен и схему
        $xml->addAttribute(
            'xmlns:xsi', 
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $xml->addAttribute(
            'xsi:schemaLocation', 
            "{$namespace} {$namespace}/sitemap.xsd", 
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $xml->addAttribute('xmlns', $namespace);

        // --- Генерация ссылок для постов ---
        foreach ($posts as $post) {
            $item = $xml->addChild('url');
            
            // <loc>
            $loc = htmlspecialchars("{$url}/" . ltrim($post['url'], '/') . ".html");
            $item->addChild('loc', $loc);
            
            // <lastmod>
            $item->addChild('lastmod', htmlspecialchars($post['updated_at']));
            
            // <changefreq>
            $item->addChild('changefreq', htmlspecialchars($changefreq_posts));
            
            // <priority>
            $item->addChild('priority', htmlspecialchars($posts_priority));
        }

        // --- Генерация ссылок для страниц ---
        foreach ($pages as $page) {
            $item = $xml->addChild('url');
            
            // <loc>
            $loc = htmlspecialchars("{$url}/page/" . ltrim($page['url'], '/') . ".html");
            $item->addChild('loc', $loc);
            
            // <lastmod>
            $item->addChild('lastmod', htmlspecialchars($page['updated_at']));
            
            // <changefreq>
            $item->addChild('changefreq', htmlspecialchars($changefreq_pages));
            
            // <priority>
            $item->addChild('priority', htmlspecialchars($pages_priority));
        }

        return $xml->asXML();
    }
}