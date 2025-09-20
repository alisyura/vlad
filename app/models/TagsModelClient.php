<?php
// app/models/TagsModelClient.php

class TagsModelClient {
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db =$pdo;
    }
    public function findPublishedPostTagsByName(string $query)
    {
        $sql = "SELECT 
                    t.url,
                    t.name,
                    COUNT(pt.post_id) AS popularity
                FROM 
                    tags t
                JOIN 
                    post_tag pt ON t.id = pt.tag_id
                JOIN 
                    posts p ON pt.post_id = p.id
                WHERE 
                    p.status = 'published'
                    AND p.article_type = 'post'
                    AND t.name LIKE :tag_name
                GROUP BY 
                    t.url, t.name
                ORDER BY 
                    popularity DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tag_name', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}