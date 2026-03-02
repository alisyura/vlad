<?php

// app/services/TaxonomyService.php

class TaxonomyService
{
    private TagsModel $tagModel;
    private AdminSeoSettingsService $seoService;
    private PDO $db;
    
    public function __construct(
        TagsModel $tagModel,
        AdminSeoSettingsService $seoService,
        PDO $db
    ) {
        $this->tagModel = $tagModel;
        $this->seoService = $seoService;
        $this->db = $db;
    }

    public function getTotalTaxonomiesCount(string $taxonomyType): int
    {
        return $this->tagModel->getTotalTaxonomiesCount($taxonomyType);
    }

    public function getTaxonomiesWithPostCount(int $limit, int $offset, string $taxonomyType): array
    {
        if (empty($taxonomyType))
        {
            throw new TaxonomyException('taxonomyType is empty');
        }
        return $this->tagModel->getTaxonomiesWithPostCount($limit, $offset, $taxonomyType);
    }

    /**
     * Получение тега с обработанными SEO-настройками
     */
    public function getTag(?int $id = null, ?string $url = null): ?array
    {
        // 1. Получаем тег
        $tag = $this->tagModel->find($id, $url);
        
        if (!$tag) {
            return null;
        }
        
        // 2. Получаем сырые SEO-настройки
        $rawSeoSettings = $this->tagModel->getSeoSettings($tag['id']);
        
        // 3. Обрабатываем SEO-настройки для отображения
        $tag['seo_settings'] = $this->seoService->processForDisplay($rawSeoSettings, $tag['url']);
        
        return $tag;
    }

    /**
     * Создание тегов с SEO-настройками
     */
    public function createTags(array $tags): bool
    {
        $this->db->beginTransaction();
        
        try {
            // 1. Создаем теги
            $this->tagModel->create($tags);
            
            // 2. Получаем ID созданных тегов
            $urls = array_column($tags, 'url');
            $createdTags = $this->tagModel->getByUrls($urls);
            $createdTagsMap = [];
            
            foreach ($createdTags as $tag) {
                $createdTagsMap[$tag['url']] = $tag;
            }
            
            // 3. Подготавливаем SEO-настройки
            $seoOperations = $this->seoService->prepareTagSeoSettings(
                $this->enrichTagsWithIds($tags, $createdTagsMap)
            );
            
            // 4. Сохраняем SEO-настройки
            $this->executeSeoOperations($seoOperations);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new TaxonomyException("Ошибка создания тегов: " . $e->getMessage());
        }
    }

    /**
     * Обновление тегов с SEO-настройками
     */
    public function updateTags(array $tagsData): void
    {
        if (empty($tagsData)) {
            throw new TaxonomyException('tagsData empty');
        }

        $this->db->beginTransaction();

        try {
            // 1. Обновляем имена тегов
            $this->tagModel->updateNames($tagsData);
            
            // 2. Получаем URL тегов
            $tagIds = array_column($tagsData, 'id');
            $tagUrls = $this->tagModel->getByIds($tagIds);
            $tagUrlsMap = array_column($tagUrls, 'url', 'id');
            
            // 3. Подготавливаем SEO-операции
            $seoOperations = $this->seoService->prepareTagSeoSettings($tagsData, $tagUrlsMap);
            
            // 4. Выполняем SEO-операции
            $this->executeSeoOperations($seoOperations);

            $this->db->commit();
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new TaxonomyException("Ошибка обновления тегов: " . $e->getMessage());
        }
    }

    public function deleteTags(array $tagIds): void
    {
        $tags = $this->tagModel->getByIds($tagIds);
        $filteredTags = array_filter($tags, fn($tag) => $tag['builtin'] != 0);

        if (!empty($filteredTags))
        {
            throw new TaxonomyException('Нельзя удалить встроенные тэги');
        }

        $this->tagModel->deleteTags($tagIds);
    }

    private function executeSeoOperations(array $operations): void
    {
        // Удаление
        if (!empty($operations['delete'])) {
            $this->tagModel->bulkDeleteSeoSettings($operations['delete']);
        }
        
        // Вставка/обновление
        if (!empty($operations['upsert'])) {
            // Получаем существующие записи
            $existingRecords = $this->tagModel->getExistingSeoSettings($operations['upsert']);
            
            // Разделяем на вставку и обновление
            $split = $this->seoService->splitUpsertOperations($operations['upsert'], $existingRecords);
            
            // Выполняем операции
            if (!empty($split['insert'])) {
                $this->tagModel->bulkInsertSeoSettings($split['insert']);
            }
            
            if (!empty($split['update'])) {
                $this->tagModel->bulkUpdateSeoSettings($split['update']);
            }
        }
    }

    /**
     * Вспомогательные методы
     */
    private function enrichTagsWithIds(array $tags, array $createdTagsMap): array
    {
        $enriched = [];
        
        foreach ($tags as $tag) {
            if (isset($createdTagsMap[$tag['url']])) {
                $enrichedTag = $tag;
                $enrichedTag['id'] = $createdTagsMap[$tag['url']]['id'];
                $enriched[] = $enrichedTag;
            }
        }
        
        return $enriched;
    }
}