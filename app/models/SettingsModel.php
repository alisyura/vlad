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
     * @param ?string $categoryUrl Выбор настроек только для этой категории.
     * @param ?string $tagUrl Выбор настроек только для этого тэга.
     * @param ?string $searchQuery Поиск настроек по названию и значению
     * @return array Плоский массив настроек, отсортированный по group_name и key.
     */
    public function getAllSeoSettingsFlat(?string $categoryUrl = '', ?string $tagUrl = '', 
        ?string $searchQuery = ''): array
    {
        $groupingColumn = 'group_name'; 
        
        // Переменная для вычисления имени группы в SQL, используется дважды
        $groupNameCalculation = "COALESCE(NULLIF(TRIM(s.`{$groupingColumn}`), ''), 'NoGroup')";

        // Инициализация массивов для условий WHERE и параметров PDO
        $where = [];
        $params = [];
        
        // 1. Фильтрация по Категории (URL)
        if (!empty($categoryUrl)) {
            // Фильтруем настройки, которые явно привязаны к этой категории
            $where[] = "c.url = :categoryUrl";
            $params[':categoryUrl'] = $categoryUrl;
        }

        // 2. Фильтрация по Тегу (URL)
        if (!empty($tagUrl)) {
            // Фильтруем настройки, которые явно привязаны к этому тегу
            $where[] = "t.url = :tagUrl";
            $params[':tagUrl'] = $tagUrl;
        }
        
        // 3. Поиск по ключу, значению или комментарию
        if (!empty($searchQuery)) {
            $searchString = "%" . $searchQuery . "%";
            $where[] = "(
                s.`key` LIKE :searchQueryKey OR 
                s.`value` LIKE :searchQueryValue OR 
                s.`comment` LIKE :searchQueryComment
            )";
            // Передаем одну и ту же строку поиска для всех полей
            $params[':searchQueryKey'] = $searchString;
            $params[':searchQueryValue'] = $searchString;
            $params[':searchQueryComment'] = $searchString;
        }

        // Построение WHERE части запроса
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

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
            
            {$whereClause} -- Вставляем WHERE clause

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
            // Используем подготовленные выражения для безопасной передачи параметров
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params); 
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Предполагается, что класс Logger доступен
            Logger::error("Error fetching all SEO settings flat with filters", [
                'categoryUrl' => $categoryUrl, 
                'tagUrl' => $tagUrl,
                'searchQuery' => $searchQuery
            ], $e);
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

    /**
     * Получает одну настройку по её ID, включая URL привязанных категории и тега.
     *
     * @param int $id ID настройки.
     * @return array|null Ассоциативный массив с данными настройки или null, если не найдена.
     */
    public function getSettingById(int $id): ?array
    {
        $sql = "
            SELECT
                s.id,
                s.`key`,
                s.`value`,
                s.`group_name`,
                s.`comment`,
                s.`builtin`,
                
                -- URL привязанной Категории (получаем через LEFT JOIN)
                c.url AS category_url,
                -- URL привязанного Тега (получаем через LEFT JOIN)
                t.url AS tag_url
            FROM
                `seo_settings` s
            LEFT JOIN `categories` c ON s.category_id = c.id
            LEFT JOIN `tags` t ON s.tag_id = t.id
            WHERE
                s.id = :id
            LIMIT 1
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            Logger::error("Error fetching SEO setting by ID: {$id}", [], $e);
            throw $e;
        }
    }

    /**
     * Обновляет настройки SEO в таблице `seo_settings` по имени группы.
     * * Обновление полей `key`, `category_id` и `tag_id` происходит только в том случае, 
     * если соответствующие входные параметры ($key, $categoryUrl, $tagUrl) не равны NULL.
     * Если URL категории или тега передан, но не найден в соответствующей таблице, 
     * связанный ID (category_id или tag_id) будет установлен в NULL.
     *
     * @param string $groupName Имя группы настройки (используется в WHERE-условии).
     * @param string|null $key Новый ключ настройки. Если NULL, поле `key` не обновляется.
     * @param string $value Новое значение настройки (всегда обновляется).
     * @param string|null $categoryUrl URL категории. Если NULL, поле `category_id` не обновляется.
     * Если не NULL, ID находится по URL и обновляет `category_id`.
     * @param string|null $tagUrl URL тега. Если NULL, поле `tag_id` не обновляется.
     * Если не NULL, ID находится по URL и обновляет `tag_id`.
     * @param string|null $comment Комментарий к настройке.
     * @return bool Возвращает TRUE при успешном выполнении запроса, FALSE в случае ошибки.
     */
    function updateSetting(
        int $id,
        string $groupName,
        ?string $key,
        string $value,
        ?string $categoryUrl,
        ?string $tagUrl,
        ?string $comment): bool 
    {
        // всегда обновляемые поля
        $setFields = [
            'group_name' => $groupName,
            'value' => $value,
            'comment' => $comment,
        ];
        
        $parameters = [];
        
        // Условие 1: Обновление поля `key`
        if ($key !== null) {
            $setFields['key'] = $key;
        }
        
        // Условие 2: Обновление поля `category_id`
        if ($categoryUrl !== null) {
            // Получаем category_id. Если URL не найден, ID будет null.
            $categoryId = $this->getEntityIdByUrl('categories', $categoryUrl);
            $setFields['category_id'] = $categoryId;
        }

        // Условие 3: Обновление поля `tag_id`
        if ($tagUrl !== null) {
            // Получаем tag_id. Если URL не найден, ID будет null.
            $tagId = $this->getEntityIdByUrl('tags', $tagUrl);
            $setFields['tag_id'] = $tagId;
        }

        // 2. Динамическое построение SQL-запроса
        $setSql = [];
        foreach ($setFields as $field => $val) {
            $paramName = ':' . $field;
            // Используем обратные кавычки для `key`, так как это зарезервированное слово в SQL.
            $fieldName = ($field === 'key') ? '`key`' : $field;

            // Если значение NULL (например, при ненахождении categoryUrl/tagUrl или если $comment был NULL), 
            // устанавливаем его как SQL NULL, иначе используем подготовленный параметр.
            if ($val === null) {
                $setSql[] = $fieldName . ' = NULL';
            } else {
                $setSql[] = $fieldName . ' = ' . $paramName;
                $parameters[$paramName] = $val;
            }
        }
        
        // WHERE-условие для поиска строки настройки (предполагаем, что group_name уникален)
        $whereSql = 'id = :id';
        $parameters[':id'] = $id;

        $sql = 'UPDATE seo_settings SET ' . implode(', ', $setSql) . ' WHERE ' . $whereSql;

        // 3. Выполнение запроса (пример с PDO)
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($parameters);
    }

    /**
     * Удаляет настройку по ее уникальному ID.
     *
     * @param int $id ID настройки для удаления.
     * @return bool Возвращает TRUE, если запрос выполнен успешно (даже если 0 строк удалено), 
     * FALSE в случае некритической ошибки выполнения.
     */
    public function deleteSetting(int $id): bool
    {
        $sql = "DELETE FROM seo_settings WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $parameters = [':id' => $id];

            return $stmt->execute($parameters);
        } catch (\PDOException $e) {
            Logger::error("Ошибка при удалении настройки", ['id' => $id], $e);
            throw $e;
        }
    }
}
