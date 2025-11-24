<?php

// app/services/SettingsService.php

class SettingsService
{
    private SettingsModel $settingsModel;
    private SettingsValidator $validator;

    public function __construct(SettingsModel $settingsModel, SettingsValidator $validator)
    {
        $this->settingsModel = $settingsModel;
        $this->validator = $validator;
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
        return $this->settingsModel->getMassSeoSettings($keys, $categoryUrls, $tagUrls);
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
     * Получает список всех уникальных имен групп настроек.
     * Исключает настройки, где group_name не указан (NULL или пустая строка).
     *
     * @return array Массив строк с именами групп, упорядоченный по алфавиту.
     */
    public function getExistingGroupNames(): array
    {
        return $this->settingsModel->getExistingGroupNames();
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

    public function settingExists($id): bool
    {
        return $this->settingsModel->getSettingById($id) !== null;
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
}