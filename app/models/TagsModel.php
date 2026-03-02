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
     * Возвращает общее количество тегов в базе данных.
     *
     * @return int
     */
    public function getTotalTaxonomiesCount($taxonomyType): int
    {
        // Проверяем, что это валидная таксономия
        if (!TaxonomyRegistry::isValid($taxonomyType)) {
            throw new InvalidArgumentException('Invalid taxonomy type');
        }
        
        $tableName = TaxonomyRegistry::getTableName($taxonomyType);

        $sql = "SELECT COUNT(*) FROM {$tableName}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
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
    public function getTaxonomiesWithPostCount(int $limit, int $offset, $taxonomyType): array
    {
        // Проверяем, что это валидная таксономия
        if (!TaxonomyRegistry::isValid($taxonomyType)) {
            throw new InvalidArgumentException('Invalid taxonomy type');
        }
        
        $tableName = TaxonomyRegistry::getTableName($taxonomyType);
        $linkTableName = TaxonomyRegistry::getLinkTableName($taxonomyType);
        $idFieldName = TaxonomyRegistry::getIdFieldName($taxonomyType);

        $sql = "
            SELECT
                t.id,
                t.url,
                t.name,
                COUNT(p_t.post_id) AS post_count,
                t.builtin
            FROM
                {$tableName} t
            LEFT JOIN
                {$linkTableName} p_t ON t.id = p_t.{$idFieldName}
            GROUP BY
                t.id
            ORDER BY
                t.name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Проверяет, заняты ли указанное имя тега и/или URL.
     *
     * @param string $name Имя тега для проверки.
     * @param string $url URL (слаг) тега для проверки.
     * @return array Возвращает массив с результатами проверки:
     * ['name_exists' => bool, 'url_exists' => bool]
     * @throws \PDOException Если происходит ошибка выполнения запроса к базе данных.
     */
    public function checkTagUniqueness(string $name, string $url): array
    {
        $sql = "
            SELECT 
                -- Если SUM вернет NULL (нет совпадений), IFNULL заменит его на 0.
                IFNULL(SUM(CASE WHEN name = :name_sum THEN 1 ELSE 0 END), 0) AS name_count,
                IFNULL(SUM(CASE WHEN url = :url_sum THEN 1 ELSE 0 END), 0) AS url_count
            FROM tags
            -- WHERE по-прежнему нужен, чтобы ограничить выборку.
            WHERE name = :name_where OR url = :url_where
        ";

        $stmt = $this->db->prepare($sql);
        
        // Передаем 4 уникально именованных параметра, чтобы избежать ошибки "Invalid parameter number".
        $stmt->execute([
            ':name_sum' => $name, 
            ':url_sum' => $url,
            ':name_where' => $name, 
            ':url_where' => $url
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result === false) {
            throw new \PDOException("Ошибка при выполнении запроса проверки уникальности тега.");
        }
        
        return [
            'name_exists' => (int)$result['name_count'] > 0,
            'url_exists' => (int)$result['url_count'] > 0
        ];
    }


    /**
     * Создает несколько тегов за один запрос.
     *
     * @param array $tags Массив с массивами данных тегов, каждый из которых содержит 'name' и 'url'.
     * @return bool Возвращает true в случае успеха, false в противном случае.
     */
    public function create(array $tags): bool
    {
        $placeholders = [];
        $binds = [];
        
        foreach ($tags as $index => $tag) {
            $placeholders[] = "(:name{$index}, :url{$index}, :robots{$index})";
            $binds[":name{$index}"] = $tag['name'];
            $binds[":url{$index}"] = $tag['url'];
            $binds[":robots{$index}"] = $tag['robots'];
        }

        $sql = "INSERT INTO tags (name, url, robots) VALUES " . implode(", ", $placeholders);
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    public function updateNames(array $tagsData): bool
    {
        $caseClauses = [];
        $robotsCaseClauses = [];
        $inPlaceholders = [];
        $binds = [];

        foreach ($tagsData as $index => $tagData) {
            // Уникальные плейсхолдеры для каждого использования
            $nameIdParam = ":name_id_{$index}";      // для name CASE
            $robotsIdParam = ":robots_id_{$index}";  // для robots CASE
            $inIdParam = ":in_id_{$index}";          // для IN()
            $nameParam = ":name_val_{$index}";
            $robotsParam = ":robots_val_{$index}";
            
            $caseClauses[] = "WHEN id = {$nameIdParam} THEN {$nameParam}";
            $robotsCaseClauses[] = "WHEN id = {$robotsIdParam} THEN {$robotsParam}";
            $inPlaceholders[] = $inIdParam;
            
            // Биндим все уникальные плейсхолдеры
            $binds[$nameIdParam] = $tagData['id'];
            $binds[$robotsIdParam] = $tagData['id'];
            $binds[$inIdParam] = $tagData['id'];
            $binds[$nameParam] = $tagData['name'];
            $binds[$robotsParam] = $tagData['robots'];
        }

        $sql = "UPDATE tags 
                SET 
                    `name` = CASE 
                        " . implode(" ", $caseClauses) . "
                        ELSE `name`
                    END,
                    `robots` = CASE 
                        " . implode(" ", $robotsCaseClauses) . "
                        ELSE `robots`
                    END
                WHERE id IN (" . implode(",", $inPlaceholders) . ")";

        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function getByIds(array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, url, builtin FROM tags WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByUrls(array $urls): array
    {
        $placeholders = implode(',', array_fill(0, count($urls), '?'));
        $sql = "SELECT id, url, name FROM tags WHERE url IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($urls);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Возвращает тег по его ID или URL.
     *
     * @param int|null $id ID тега.
     * @param string|null $url URL тега.
     * @return array|null Возвращает массив с данными тега или null, если тег не найден.
     */
    public function find(?int $id = null, ?string $url = null): ?array
    {
        $where = [];
        $binds = [];
        
        if ($id !== null) {
            $where[] = "id = :id";
            $binds[':id'] = $id;
        }

        if ($url !== null) {
            $where[] = "url = :url";
            $binds[':url'] = $url;
        }

        if (empty($where)) {
            return null;
        }

        $sql = "SELECT id, url, name, robots FROM tags WHERE " . implode(" AND ", $where);
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function getSeoSettings(int $tagId): array
    {
        $sql = "SELECT `key`, `value` FROM seo_settings WHERE tag_id = :tag_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tag_id', $tagId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Массовые операции для SEO
    public function bulkDeleteSeoSettings(array $settings): bool
    {
        if (empty($settings)) {
            return false;
        }
        
        $conditions = [];
        $binds = [];
        
        foreach ($settings as $index => $setting) {
            $conditions[] = "(tag_id = :tag_id{$index} AND `key` = :key{$index})";
            $binds[":tag_id{$index}"] = $setting['tag_id'];
            $binds[":key{$index}"] = $setting['key'];
        }
        
        $sql = "DELETE FROM seo_settings WHERE " . implode(" OR ", $conditions);
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function bulkInsertSeoSettings(array $settings): bool
    {
        if (empty($settings)) {
            return false;
        }
        
        $values = [];
        $binds = [];
        
        foreach ($settings as $index => $setting) {
            $values[] = "('Tegi', '', :tag_id{$index}, :key{$index}, :value{$index})";
            $binds[":tag_id{$index}"] = $setting['tag_id'];
            $binds[":key{$index}"] = $setting['key'];
            $binds[":value{$index}"] = $setting['value'];
        }
        
        $sql = "INSERT INTO seo_settings (group_name, comment, tag_id, `key`, `value`) 
                VALUES " . implode(", ", $values);
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function bulkUpdateSeoSettings(array $settings): bool
    {
        if (empty($settings)) {
            return false;
        }
        
        $caseClauses = [];
        $ids = [];
        $binds = [];
        
        foreach ($settings as $index => $setting) {
            $caseClauses[] = "WHEN id = :id{$index} THEN :value{$index}";
            $ids[] = ":in_id{$index}";
            $binds[":id{$index}"] = $setting['id'];
            $binds[":value{$index}"] = $setting['value'];
            $binds[":in_id{$index}"] = $setting['id'];
        }
        
        $sql = "UPDATE seo_settings SET `value` = CASE " . implode(" ", $caseClauses) . " END 
                WHERE id IN (" . implode(",", $ids) . ")";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function getExistingSeoSettings(array $settings): array
    {
        if (empty($settings)) {
            return [];
        }
        
        $conditions = [];
        $binds = [];
        
        foreach ($settings as $index => $setting) {
            $conditions[] = "(tag_id = :tag_id{$index} AND `key` = :key{$index})";
            $binds[":tag_id{$index}"] = $setting['tag_id'];
            $binds[":key{$index}"] = $setting['key'];
        }
        
        $sql = "SELECT id, tag_id, `key` FROM seo_settings 
                WHERE " . implode(" OR ", $conditions);
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /**
     * Удаляет несколько тегов по их ID.
     * Этот метод выполняет массовое удаление в рамках одной транзакции для обеспечения атомарности.
     *
     * @param array $tagIds Массив с ID тегов для удаления.
     * @return bool Возвращает true в случае успеха, false в противном случае.
     */
    public function deleteTags(array $tagIds): void
    {
        if (empty($tagIds)) {
            throw new TaxonomyException('deleteTags. tagIds empty');
        }

        $this->db->beginTransaction();

        try {
            // Создаем массив уникальных именованных параметров
            $placeholders = [];
            $binds = [];
            foreach ($tagIds as $index => $id) {
                $paramName = ":id_{$index}";
                $placeholders[] = $paramName;
                $binds[$paramName] = $id;
            }

            $sql = "DELETE FROM tags WHERE id IN (" . implode(", ", $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }

            $stmt->execute();

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            Logger::error('Error in deleteTags. ', $tagIds, $e);
            throw $e;
        }
    }
}