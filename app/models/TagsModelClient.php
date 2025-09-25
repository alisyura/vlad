<?php
// app/models/TagsModelClient.php

/**
 * Модель для работы с данными тегов на клиентской части.
 *
 * Отвечает за извлечение информации о тегах из базы данных,
 * в частности, связанных с опубликованными постами.
 */
class TagsModelClient {
    private PDO $db;

    /**
     * Конструктор TagsModelClient.
     *
     * @param PDO $pdo Объект PDO для работы с базой данных, внедряется через DI.
     */
    public function __construct(PDO $pdo)
    {
        $this->db =$pdo;
    }

    /**
     * Ищет теги, связанные с опубликованными постами, по имени.
     *
     * Запрос ищет теги, чьи имена содержат заданную строку ($query),
     * и возвращает их URL, имя и популярность (количество связанных опубликованных постов).
     * Результаты отсортированы по популярности в порядке убывания.
     *
     * @param string $query Строка для поиска в именах тегов.
     * @return array Массив ассоциативных массивов с данными тегов (url, name, popularity).
     */
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