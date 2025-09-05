<?php
// app/models/TagsModel.php

class TagsModel extends BaseModel {
    /**
     * Ищет теги по части имени и возвращает их с количеством связанных постов.
     *
     * Метод выполняет поиск по имени тега, используя оператор LIKE. Результаты
     * сортируются по убыванию популярности (количеству постов) и ограничиваются
     * первыми 10 записями.
     *
     * @param string $query Часть имени тега для поиска.
     * @return array Массив ассоциативных массивов с данными найденных тегов.
     * Пример: [['name' => 'php', 'url' => 'php', 'popularity_count' => 15], ...]
     */
    public function searchTagsByName(string $query)
    {
        $sql = "SELECT
                    t.name,
                    t.url,
                    COUNT(pt.post_id) as popularity_count
                FROM
                    tags t
                LEFT JOIN
                    post_tag pt ON t.id = pt.tag_id
                WHERE
                    t.name LIKE :query
                GROUP BY
                    t.id
                ORDER BY
                    popularity_count DESC
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получает список всех тегов в алфавитном порядке с количеством постов.
     *
     * Метод возвращает данные тега (id, url, name) и количество постов,
     * в которых он был использован, используя LEFT JOIN и COUNT.
     *
     * @return array Массив ассоциативных массивов с данными тегов.
     * Пример: [['id' => '1', 'url' => 'php', 'name' => 'PHP', 'post_count' => 15], ...]
     */
    public function getTagsWithPostCount(): array
    {
        $sql = "
            SELECT
                t.id,
                t.url,
                t.name,
                COUNT(p_t.post_id) AS post_count
            FROM
                tags t
            LEFT JOIN
                post_tag p_t ON t.id = p_t.tag_id
            GROUP BY
                t.id
            ORDER BY
                t.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}