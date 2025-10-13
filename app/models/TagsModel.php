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
    public function getTotalTagsCount(): int
    {
        $sql = "SELECT COUNT(*) FROM tags";
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
    public function getTagsWithPostCount(int $limit, int $offset): array
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
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает тег по его ID или URL.
     *
     * @param int|null $id ID тега.
     * @param string|null $url URL тега.
     * @return array|null Возвращает массив с данными тега или null, если тег не найден.
     */
    public function getTag(?int $id = null, ?string $url = null): ?array
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
            return null; // Нет параметров для поиска
        }

        $sql = "SELECT id, url, name FROM tags WHERE " . implode(" AND ", $where);
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tag ?: null;
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
    public function createTags(array $tags): bool
    {
        if (empty($tags)) {
            return false;
        }

        $placeholders = [];
        $binds = [];
        
        // Динамически создаем строку с заполнителями
        foreach ($tags as $index => $tag) {
            $placeholders[] = "(:name{$index}, :url{$index})";
            $binds[":name{$index}"] = $tag['name'];
            $binds[":url{$index}"] = $tag['url'];
        }

        $sql = "INSERT INTO tags (name, url) VALUES " . implode(", ", $placeholders);

        $stmt = $this->db->prepare($sql);
        
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Массовое обновление данных тегов.
     * Этот метод выполняет все обновления в одном SQL-запросе с помощью оператора CASE.
     * Он был изменен, чтобы обеспечить уникальные имена для всех именованных параметров.
     *
     * @param array $tagsData Массив с данными для обновления тегов.
     * @return bool Возвращает true в случае успеха, false в противном случае.
     */
    public function updateTags(array $tagsData): bool
    {
        if (empty($tagsData)) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $caseClauses = [];
            $inPlaceholders = [];
            $binds = [];

            foreach ($tagsData as $index => $tagData) {
                if (!isset($tagData['id']) || !isset($tagData['name'])) {
                    continue; // Пропускаем некорректные записи
                }
                
                // Создаем уникальные имена параметров
                $caseIdParam = ":case_id_{$index}";
                $nameParam = ":name_{$index}";
                $inIdParam = ":in_id_{$index}";
                
                $caseClauses[] = "WHEN id = {$caseIdParam} THEN {$nameParam}";
                $inPlaceholders[] = $inIdParam;
                
                // Связываем каждое уникальное имя с данными
                $binds[$caseIdParam] = $tagData['id'];
                $binds[$nameParam] = $tagData['name'];
                $binds[$inIdParam] = $tagData['id'];
            }
            
            if (empty($caseClauses)) {
                $this->db->rollBack();
                return false;
            }

            $sql = "UPDATE tags SET `name` = CASE " . implode(" ", $caseClauses) . " END WHERE id IN (" . implode(",", $inPlaceholders) . ")";

            $stmt = $this->db->prepare($sql);
            
            foreach ($binds as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            Logger::error('Error in updateTags. ' . $e->getTraceAsString(), $tagsData);
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Удаляет несколько тегов по их ID.
     * Этот метод выполняет массовое удаление в рамках одной транзакции для обеспечения атомарности.
     *
     * @param array $tagIds Массив с ID тегов для удаления.
     * @return bool Возвращает true в случае успеха, false в противном случае.
     */
    public function deleteTags(array $tagIds): bool
    {
        if (empty($tagIds)) {
            return false;
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

            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            Logger::error('Error in updateTags. ' . $e->getTraceAsString(), $tagIds);
            $this->db->rollBack();
            return false;
        }
    }
}