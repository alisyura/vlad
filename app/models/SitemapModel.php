<?php
// app/models/SitemapModel.php

class SitemapModel {
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db =$pdo;
    }
    public function getSitemapData()
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
}