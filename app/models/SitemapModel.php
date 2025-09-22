<?php
// app/models/SitemapModel.php

/**
 * Class SitemapModel
 *
 * Модель для работы с данными, связанными с картой сайта.
 * Содержит методы для получения данных о постах и страницах.
 */
class SitemapModel {
    private PDO $db;

    /**
     * Конструктор класса SitemapModel.
     *
     * @param PDO $pdo Объект подключения к базе данных. Внедряется из Dependency Injection
     */
    public function __construct(PDO $pdo)
    {
        $this->db =$pdo;
    }

    /**
     * Получает все данные для карты сайта (sitemap) из базы данных.
     *
     * Запрос объединяет данные о постах и страницах, сортируя их сначала по типу,
     * затем по ID категории и дате обновления.
     *
     * @return array Массив данных, готовых для отображения в карте сайта.
     */
    public function getSitemapData(): array
    {
        $sql="(
            SELECT 
                'post' AS type,
                c.id AS category_id,
                c.name AS category_name,
                c.url AS category_url,
                p.title AS post_title,
                p.url AS post_url,
                p.updated_at AS updated_at
            FROM 
                categories c
            JOIN 
                post_category pc ON c.id = pc.category_id
            JOIN 
                posts p ON pc.post_id = p.id
            WHERE 
                p.article_type = 'post'
                AND p.status = 'published'
        )
        UNION
        (
            SELECT 
                'page' AS type,
                NULL AS category_id,
                NULL AS category_name,
                NULL AS category_url,
                p.title AS post_title,
                p.url AS post_url,
                p.updated_at AS updated_at
            FROM 
                posts p
            WHERE 
                p.article_type = 'page'
                AND p.status = 'published'
        )
        ORDER BY 
            FIELD(type, 'post', 'page'), -- Сначала посты, потом страницы
            category_id ASC,
            updated_at deSC;";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получает общее количество постов определенного типа.
     *
     * @param string $articleType Тип статьи ('post' или 'page').
     * @return int Общее количество опубликованных постов.
     */
    public function getPostsCount(string $articleType): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM posts
            WHERE status = 'published' AND article_type = :article_type");
        $stmt->execute([':article_type' => $articleType]);
        $row = $stmt->fetch();

        return (int) $row['total'];
    }

    /**
     * Получает часть постов/страниц для указанного типа и страницы.
     * 
     * @param int $offset Неотрицательное смещение
     * @param string 'post'|'page' $type Тип записей
     * @return array Массив URL-ов и дат обновления
     */
    public function getPostsByOffsetNum(int $offset, string $type, int $max_urls) : array
    {
        $sql = "
            SELECT url, DATE_FORMAT(updated_at, '%Y-%m-%dT%T+00:00') AS updated_at FROM posts 
            WHERE status = 'published' AND article_type = :type
            LIMIT :max_urls OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':max_urls', $max_urls, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}