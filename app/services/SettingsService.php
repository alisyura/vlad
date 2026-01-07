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
}