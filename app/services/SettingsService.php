<?php

// app/services/SettingsService.php

class SettingsService
{
    private SettingsModel $settingsModel;

    public function __construct(SettingsModel $settingsModel)
    {
        $this->settingsModel = $settingsModel;
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
     * @return array Сгруппированный массив настроек.
     */
    public function getGroupedSettingsForDisplay(): array
    {
        $rawSettings = $this->settingsModel->getAllSeoSettingsFlat();
        
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
}