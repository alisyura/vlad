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
}
