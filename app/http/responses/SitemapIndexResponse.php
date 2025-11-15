<?php 

// app/http/responses/SitemapIndexResponse.php

class SitemapIndexResponse extends XmlResponse
{
    protected const XML_ROOT_NODE = 'sitemapindex'; 

    /**
     * Конструктор SitemapIndexResponse.
     * @param int $chunks_posts Количество частей для постов.
     * @param int $chunks_pages Количество частей для страниц.
     * @param string $url Базовый URL.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     */
    public function __construct(
        int $chunks_posts, 
        int $chunks_pages, 
        string $url, 
        int $statusCode = 200, 
        array $headers = []
    ) {
        $xmlContent = $this->generateSitemapIndexXml(
            $chunks_posts, 
            $chunks_pages, 
            $url
        );
        
        parent::__construct($xmlContent, $statusCode, $headers); 
    }

    /**
     * Генерирует XML-строку Sitemap Index с использованием SimpleXMLElement.
     * @param int $chunks_posts
     * @param int $chunks_pages
     * @param string $url
     * @return string
     */
    private function generateSitemapIndexXml(
            int $chunks_posts, 
            int $chunks_pages, 
            string $url): string 
    {
        $namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        
        // Создаем корневой элемент с нужными пространствами имен и атрибутами
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><sitemapindex/>', 
            0, 
            false, 
            $namespace
        );

        // Добавляем атрибуты пространства имен
        $xml->addAttribute(
            'xmlns:xsi', 
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $xml->addAttribute(
            'xsi:schemaLocation', 
            "{$namespace} {$namespace}/siteindex.xsd", 
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $xml->addAttribute('xmlns', $namespace);

        $lastMod = date('Y-m-d'); // Текущая дата

        // Генерация ссылок для постов
        for ($i = 1; $i <= $chunks_posts; $i++) {
            $sitemap = $xml->addChild('sitemap');
            $sitemap->addChild('loc', htmlspecialchars("{$url}/sitemap-posts-{$i}.xml"));
            $sitemap->addChild('lastmod', $lastMod);
        }

        // Генерация ссылок для страниц
        for ($i = 1; $i <= $chunks_pages; $i++) {
            $sitemap = $xml->addChild('sitemap');
            $sitemap->addChild('loc', htmlspecialchars("{$url}/sitemap-pages-{$i}.xml"));
            $sitemap->addChild('lastmod', $lastMod);
        }

        return $xml->asXML();
    }
}