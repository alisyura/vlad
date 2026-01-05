<?php

// app/services/SeoSettingsService.php

class SeoSettingsService
{
    private TagsModel $tagsModel;
    private PDO $db;

    public const TRANSFORM_SUFFIXES = [
        'caption', 
        'caption_desc', 
        'title', 
        'description', 
        'keywords'
    ];

    public function __construct(TagsModel $tagsModel, PDO $db)
    {
        $this->tagsModel = $tagsModel;
        $this->db = $db;
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