<?php

// app/services/SeoSettingsService.php

class AdminSeoSettingsService
{
    private SettingsValidator $validator;
    private SettingsModel $settingsModel;

    public const TRANSFORM_SUFFIXES = [
        'caption', 
        'caption_desc', 
        'title', 
        'description', 
        'keywords'
    ];

    public function __construct(SettingsValidator $validator,
        SettingsModel $settingsModel)
    {
        $this->validator = $validator;
        $this->settingsModel = $settingsModel;
    }

    /**
     * Получает одну настройку по её ID, включая URL привязанных категории и тега.
     *
     * @param int $id ID настройки.
     * @return array|null Ассоциативный массив с данными настройки или null, если не найдена.
     */
    public function getSettingById(int $id): ?array
    {
        return $this->settingsModel->getSettingById($id);
    }

    public function updateSetting(
        int $id,
        string $groupName,
        ?string $key,
        string $value,
        ?string $categoryUrl,
        ?string $tagUrl,
        ?string $comment): bool 
    {
        $errors = $this->validator->validateUpdate($id, $key, 
            $value, $categoryUrl, $tagUrl);
        if (!empty($errors))
        {
            throw new UserDataException('Некорректно заполнены данные', $errors);
        }
        if (!$this->settingsModel->updateSetting($id, $groupName, $key, $value, 
            $categoryUrl, $tagUrl, $comment))
        {
            throw new SettingsException(
                "Не удалось сохранить или обновить настройку"
            );
        }

        return true;
    }

    /**
     * Удаляет настройку и проверяет, была ли строка затронута.
     *
     * @param int $id ID настройки для удаления.
     * @return bool Возвращает TRUE при успешном удалении.
     * @throws InvalidArgumentException Если настройка с указанным ID не найдена.
     */
    public function deleteSetting(int $id): bool
    {
        if (!filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw new \InvalidArgumentException("Некорректный ID настройки.");
        }
        
        return $this->settingsModel->deleteSetting($id);
    }
    
    public function createSetting(
        string $groupName, 
        string $key, 
        string $value, 
        ?string $categoryUrl = null, 
        ?string $tagUrl = null, 
        ?string $comment = null): void
    {
        $errors = $this->validator->validateCreate($key, 
            $value, $categoryUrl, $tagUrl);
        if (!empty($errors))
        {
            throw new UserDataException('Некорректно заполнены данные', $errors);
        }
        $this->settingsModel->createSetting($groupName, $key, $value, 
            $categoryUrl, $tagUrl, $comment);
    }

    /**
     * @deprecated
     */
    public function settingExists($id): bool
    {
        return $this->settingsModel->getSettingById($id) !== null;
    }

    /**
     * Получает список всех уникальных имен групп настроек.
     * Исключает настройки, где group_name не указан (NULL или пустая строка).
     *
     * @return array Массив строк с именами групп, упорядоченный по алфавиту.
     */
    public function getExistingGroupNames(): array
    {
        return $this->settingsModel->getExistingGroupNames();
    }

    /**
     * Вспомогательный метод для преобразования плоского массива настроек 
     * в многомерный массив, сгруппированный по 'group_name'.
     *
     * @param array $rawSettings Сырой массив настроек, полученный из БД.
     * @param string $groupKeyName Поле с названием группы.
     * @return array Сгруппированный массив.
     */
    private function groupSettingsArray(array $rawSettings, string $groupKeyName): array
    {
        $groupedSettings = [];

        foreach ($rawSettings as $setting) {
            $groupName = $setting[$groupKeyName];

            // Формируем запись, исключая 'group_name'
            $record = [
                'id'            => $setting['id'],
                'key'           => $setting['key'],
                'value'         => $setting['value'],
                'comment'       => $setting['comment'],
                'builtin'       => $setting['builtin'],
                'category_name' => $setting['category_name'],
                'category_url'  => $setting['category_url'],
                'tag_name'      => $setting['tag_name'],
                'tag_url'       => $setting['tag_url'],
            ];

            // Добавляем запись в соответствующую группу
            if (!isset($groupedSettings[$groupName])) {
                $groupedSettings[$groupName] = [];
            }
            
            $groupedSettings[$groupName][] = $record;
        }

        return $groupedSettings;
    }

    /**
     * Получает все настройки SEO, сгруппированные по имени группы 
     * (или 'NoGroup' для записей без группы).
     * * Здесь можно добавить логику кеширования, преобразования данных
     * или фильтрации, прежде чем они попадут в контроллер.
     *
     * @param ?string $categoryUrl Выбор настроек только для этой категории.
     * @param ?string $tagUrl Выбор настроек только для этого тэга.
     * @param ?string $searchQuery Поиск настроек по названию и значению
     * @return array Сгруппированный массив настроек.
     */
    public function getGroupedSettingsForDisplay(?string $categoryUrl = '', ?string $tagUrl = '', 
        ?string $searchQuery = ''): array
    {
        $rawSettings = $this->settingsModel->getAllSeoSettingsFlat($categoryUrl,
            $tagUrl, $searchQuery);
        
        return $this->groupSettingsArray($rawSettings, 'group_name');
    }

    /**
     * Подготавливает SEO-данные для тегов
     */
    public function prepareTagSeoSettings(array $tagsData, array $tagUrls = []): array
    {
        $operations = ['delete' => [], 'upsert' => []];
        
        foreach ($tagsData as $tagData) {
            if (!isset($tagData['id'])) {
                continue;
            }
            
            $tagId = $tagData['id'];
            $tagUrl = $tagUrls[$tagId] ?? ($tagData['url'] ?? '');
            
            if (!$tagUrl) {
                continue;
            }
            
            foreach (self::TRANSFORM_SUFFIXES as $field) {
                if (!array_key_exists($field, $tagData)) {
                    continue;
                }
                
                $value = $tagData[$field];
                $key = "tag_{$tagUrl}_{$field}";
                
                if ($value === null || $value === '') {
                    $operations['delete'][] = [
                        'tag_id' => $tagId,
                        'key' => $key
                    ];
                } else {
                    $operations['upsert'][] = [
                        'tag_id' => $tagId,
                        'key' => $key,
                        'value' => $value
                    ];
                }
            }
        }
        
        return $operations;
    }
    
    /**
     * Обрабатывает SEO-настройки для отображения
     */
    public function processForDisplay(array $rawSeoSettings, string $tagUrl): array
    {
        $prefix = "tag_{$tagUrl}_";
        $processed = [];
        
        foreach ($rawSeoSettings as $row) {
            if (empty($row['key'])) {
                continue;
            }
            
            $finalKey = $row['key'];
            
            if (str_starts_with($row['key'], $prefix)) {
                $suffix = substr($row['key'], strlen($prefix));
                if (in_array($suffix, self::TRANSFORM_SUFFIXES)) {
                    $finalKey = $suffix;
                }
            }
            
            $processed[$finalKey] = $row['value'];
        }
        
        return $processed;
    }
    
    /**
     * Разделяет upsert-операции на вставку и обновление
     */
    public function splitUpsertOperations(array $upsertData, array $existingRecords): array
    {
        $existingMap = [];
        foreach ($existingRecords as $record) {
            $key = $record['tag_id'] . '||' . $record['key'];
            $existingMap[$key] = $record['id'];
        }
        
        $toInsert = [];
        $toUpdate = [];
        
        foreach ($upsertData as $setting) {
            $key = $setting['tag_id'] . '||' . $setting['key'];
            
            if (isset($existingMap[$key])) {
                $toUpdate[] = [
                    'id' => $existingMap[$key],
                    'value' => $setting['value']
                ];
            } else {
                $toInsert[] = $setting;
            }
        }
        
        return ['insert' => $toInsert, 'update' => $toUpdate];
    }
}