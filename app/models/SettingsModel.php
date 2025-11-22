<?php 

// app/models/SettingsModel.php

class SettingsModel extends BaseModel {
    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }

    private function bindKeyPlaceholders(array $keys, string $prefix, array &$params): string
    {
        if (empty($keys)) {
            return '';
        }
        $keyPlaceholders = [];
        foreach ($keys as $i => $k) {
            // Генерируем уникальное имя, например, :k_g_0
            $keyPlaceholder = ':k_' . $prefix . $i; 
            $keyPlaceholders[] = $keyPlaceholder;
            $params[$keyPlaceholder] = $k;
        }
        return " AND s.`key` IN (" . implode(', ', $keyPlaceholders) . ") ";
    }
    
    /**
     * Строит SQL-секцию UNION для конкретной сущности (Категория или Тег).
     */
    private function buildEntityUnionSql(
        string $entityTable,    // 'categories' или 'tags'
        string $entityIdColumn, // 'category_id' или 'tag_id' в seo_settings
        array $urls,            // Массив URL-адресов
        string $keyPrefix,      // Префикс для плейсхолдеров ключей (например, 'c_')
        string $nullColumn,     // Колонка, которая должна быть NULL (tag_id или category_id)
        array $keys,            // Основной массив ключей
        array &$params          // Массив параметров PDO (по ссылке)
    ): ?string {
        if (empty($urls)) {
            return null;
        }

        $keysInClause = $this->bindKeyPlaceholders($keys, $keyPrefix, $params);
        
        $urlPlaceholders = [];
        $i = 0;
        foreach ($urls as $url) {
            // Генерируем уникальные плейсхолдеры для URL
            $urlPlaceholder = ':url_' . $keyPrefix . $i++;
            $urlPlaceholders[] = $urlPlaceholder;
            $params[$urlPlaceholder] = $url;
        }
        $urlInClause = implode(', ', $urlPlaceholders);
        
        return "
            (
                SELECT s.`key`, s.`value`, s.`category_id`, s.`tag_id`
                FROM `seo_settings` s
                JOIN `{$entityTable}` e ON s.{$entityIdColumn} = e.id
                WHERE e.url IN ({$urlInClause}) 
                  AND s.{$nullColumn} IS NULL 
                  {$keysInClause}
            )
        ";
    }

    /**
     * Получает массовые настройки, строго соблюдая приоритет:
     * 1. Если заданы Категории/Теги, ищем только по ним.
     * 2. Если не заданы Категории/Теги, ищем только глобальные.
     * 3. Если $keys пустой, ищем все ключи в выбранном контексте.
     *
     * @param array $keys Массив ключей настроек (если пустой, ищет все ключи).
     * @param array $categoryUrls Массив ID категорий.
     * @param array $tagUrls Массив ID тегов.
     * @return array Список найденных настроек (key, value, category_id, tag_id).
     */
    public function getMassSeoSettings(array $keys = [], array $categoryUrls = [], array $tagUrls = []): array
    {
        $unionParts = [];
        $params = [];
        $isEntitySearch = !empty($categoryUrls) || !empty($tagUrls);

        // --- ГЛОБАЛЬНЫЕ НАСТРОЙКИ (k_g_) ---
        // Включаем всегда, чтобы обеспечить наследование/резерв.
        if (!$isEntitySearch || $isEntitySearch) { 
            $keysInClauseGlobal = $this->bindKeyPlaceholders($keys, 'g_', $params);
            
            $globalSql = "
                (
                    SELECT s.`key`, s.`value`, s.`category_id`, s.`tag_id`
                    FROM `seo_settings` s
                    WHERE s.category_id IS NULL 
                      AND s.tag_id IS NULL
                      {$keysInClauseGlobal}
                )
            ";
            $unionParts[] = $globalSql;
        }

        // --- НАСТРОЙКИ КАТЕГОРИЙ (k_c_) ---
        $categorySql = $this->buildEntityUnionSql(
            'categories', 
            'category_id', 
            $categoryUrls, 
            'c_', 
            'tag_id',
            $keys,
            $params
        );
        if ($categorySql) {
            $unionParts[] = $categorySql;
        }
        
        // --- НАСТРОЙКИ ТЕГОВ (k_t_) ---
        $tagSql = $this->buildEntityUnionSql(
            'tags', 
            'tag_id', 
            $tagUrls, 
            't_', 
            'category_id',
            $keys,
            $params
        );
        if ($tagSql) {
            $unionParts[] = $tagSql;
        }

        if (empty($unionParts)) {
            return [];
        }
        
        // Собираем SQL-запрос
        $sql = implode("\nUNION ALL\n", $unionParts);
        
        // --- Выполнение запроса и перестроение массива ---
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params); 
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $indexedSettings = [];
            // Настройки, пришедшие последними (сущности), перезапишут глобальные (наследование)
            foreach ($results as $row) {
                $key = $row['key'];
                unset($row['key']); 
                $indexedSettings[$key] = $row;
            }

            return $indexedSettings;

        } catch (PDOException $e) {
            // Используйте ваше реальное логирование
            Logger::error("Error fetching mass SEO settings (UNION): ", ['keys' => $keys, 'categoryUrls' => $categoryUrls, 'tagUrls' => $tagUrls], $e); 
            throw $e;
        }
    }

    /**
     * Получает список всех настроек из таблицы seo_settings в виде плоского массива,
     * включая данные привязанных сущностей (категорий, тегов).
     *
     * @return array Плоский массив настроек, отсортированный по group_name и key.
     */
    public function getAllSeoSettingsFlat(): array
    {
        $groupingColumn = 'group_name'; 
        
        // Переменная для вычисления имени группы в SQL, используется дважды
        $groupNameCalculation = "COALESCE(NULLIF(TRIM(s.`{$groupingColumn}`), ''), 'NoGroup')";

        $sql = "
            SELECT
                s.id,
                {$groupNameCalculation} AS group_name,
                s.`key`,
                s.`value`,
                s.`comment`,
                s.`builtin`,
                
                -- Поля Категории
                s.`category_id`,
                c.name AS category_name,
                c.url AS category_url,
                
                -- Поля Тега
                s.`tag_id`,
                t.name AS tag_name,
                t.url AS tag_url
            FROM
                `seo_settings` s
            -- LEFT JOIN, так как настройки могут быть глобальными
            LEFT JOIN `categories` c ON s.category_id = c.id
            LEFT JOIN `tags` t ON s.tag_id = t.id
            ORDER BY
                -- 'NoGroup' идет последним (CASE WHEN возвращает 1)
                CASE 
                    WHEN {$groupNameCalculation} = 'NoGroup' THEN 1
                    ELSE 0
                END ASC,
                -- Затем сортируем по алфавиту остальные группы
                group_name ASC,
                -- И по ключу внутри группы
                s.`key` ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(); 
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            Logger::error("Error fetching all SEO settings flat", [], $e);
            throw $e;
        }
    }

    /**
     * Получает список всех уникальных имен групп настроек.
     * Исключает настройки, где group_name не указан (NULL или пустая строка).
     *
     * @return array Массив строк с именами групп, упорядоченный по алфавиту.
     */
    public function getExistingGroupNames(): array
    {
        $groupingColumn = 'group_name'; 
        
        $sql = "
            SELECT DISTINCT
                `{$groupingColumn}` AS group_name
            FROM
                `seo_settings`
            WHERE
                -- Исключаем NULL и пустые/состоящие из пробелов строки
                TRIM(`{$groupingColumn}`) != '' AND `{$groupingColumn}` IS NOT NULL
            ORDER BY
                group_name ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(); 
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            Logger::error("Error fetching existing group names: ", [], $e);
            throw $e;
        }
    }

    /**
     * Возвращает ID сущности (категории или тега) по ее URL.
     * * @param string $tableName Имя таблицы ('categories' или 'tags').
     * @param string $url URL сущности.
     * @return int|null ID сущности или NULL, если не найдена.
     */
    private function getEntityIdByUrl(string $tableName, string $url): ?int
    {
        if (empty($url)) {
            return null;
        }

        $sql = "SELECT `id` FROM `{$tableName}` WHERE `url` = :url";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':url' => $url]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? (int)$result : null;
    }
    
    /**
     * Создает новую настройку в таблице seo_settings.
     * @param string $groupName Имя группы.
     * @param string $key Ключ настройки.
     * @param string $value Значение настройки.
     * @param string|null $categoryUrl URL категории (для настроек категории).
     * @param string|null $tagUrl URL тега (для настроек тега).
     * @param string|null $comment Комментарий.
     * @return int ID созданной настройки.
     * @throws Exception Если и категория, и тег заданы, или не найдена сущность по URL.
     */
    public function createSetting(
        string $groupName, 
        string $key, 
        string $value, 
        ?string $categoryUrl = null, 
        ?string $tagUrl = null, 
        ?string $comment = null
    ): int {
        $categoryId = null;
        $tagId = null;
        
        // 1. Проверяем, что задана только одна сущность
        if (!empty($categoryUrl) && !empty($tagUrl)) {
            throw new \InvalidArgumentException('Нельзя одновременно задать URL категории и URL тега.');
        }

        // 2. Получаем ID сущностей
        if (!empty($categoryUrl)) {
            $categoryId = $this->getEntityIdByUrl('categories', $categoryUrl);
            if ($categoryId === null) {
                throw new \RuntimeException("Категория с URL '{$categoryUrl}' не найдена.");
            }
        }

        if (!empty($tagUrl)) {
            $tagId = $this->getEntityIdByUrl('tags', $tagUrl);
            if ($tagId === null) {
                throw new \RuntimeException("Тег с URL '{$tagUrl}' не найден.");
            }
        }
        
        // 3. Составляем SQL-запрос
        $sql = "
            INSERT INTO `seo_settings` 
                (`group_name`, `key`, `value`, `comment`, `category_id`, `tag_id`)
            VALUES 
                (:groupName, :key, :value, :comment, :categoryId, :tagId)
        ";
        
        $params = [
            ':groupName' => $groupName,
            ':key'       => $key,
            ':value'     => $value,
            ':comment'   => $comment,
            ':categoryId'=> $categoryId,
            ':tagId'     => $tagId,
        ];

        // 4. Выполняем запрос
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params); 
            
            // Возвращаем ID только что созданной записи
            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            // Реальное логирование
            Logger::error("Error creating SEO setting: ", $params, $e);
            // Обработка возможного дублирования ключа (например, UNIQUE-индекс)
            if ($e->getCode() === '23000') { // 23000 - Integrity constraint violation
                 throw new \RuntimeException("Настройка с ключом '{$key}' уже существует в данном контексте.", 0, $e);
            }
            throw $e;
        }
    }
}
