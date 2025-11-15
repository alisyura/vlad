<?php

// app/http/factories/ApplicationResponseFactory.php

class ApplicationResponseFactory extends ResponseFactory
{
    /**
     * Создает HTTP-ответ, содержащий корневой XML-файл карты сайта (sitemapindex.xml).
     *
     * Метод инкапсулирует создание специфического для приложения
     * объекта ответа {@see SitemapIndexResponse}.
     *
     * @param int $chunks_posts Количество частей (чанков) карты сайта для постов.
     * @param int $chunks_pages Количество частей (чанков) карты сайта для страниц.
     * @param string $url Базовый URL сайта, используемый для построения ссылок.
     * @param int $statusCode HTTP-код статуса ответа (по умолчанию 200).
     * @param array $headers Дополнительные HTTP-заголовки.
     * @return SitemapIndexResponse Возвращает объект ответа с готовым XML-контентом.
     */
    public function createSitemapIndexResponse(
        int $chunks_posts, 
        int $chunks_pages, 
        string $url, 
        int $statusCode = 200, 
        array $headers = []
    ): Response
    {
        return new SitemapIndexResponse(
            $chunks_posts, 
            $chunks_pages, 
            $url, 
            $statusCode, 
            $headers
        );
    }

    /**
     * Создает ответ для части карты сайта (<urlset>).
     *
     * @param string $url Базовый URL.
     * @param array $posts Массив данных о постах.
     * @param array $pages Массив данных о страницах.
     * @param string $changefreq_posts Частота изменения постов.
     * @param string $posts_priority Приоритет постов.
     * @param string $changefreq_pages Частота изменения страниц.
     * @param string $pages_priority Приоритет страниц.
     * @param int $statusCode HTTP-код статуса.
     * @param array $headers Дополнительные заголовки.
     * @return SitemapPartResponse Возвращает объект ответа с готовым XML-контентом.
     */
    public function createSitemapPartResponse(
        string $url,
        array $posts,
        array $pages,
        string $changefreq_posts,
        string $posts_priority,
        string $changefreq_pages,
        string $pages_priority,
        int $statusCode = 200, 
        array $headers = []
    ): Response
    {
        return new SitemapPartResponse(
            $url,
            $posts,
            $pages,
            $changefreq_posts,
            $posts_priority,
            $changefreq_pages,
            $pages_priority,
            $statusCode,
            $headers
        );
    }
}