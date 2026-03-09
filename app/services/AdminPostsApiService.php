<?php
// app/services/AdminPostsApiService.php


class AdminPostsApiService
{
    private PostModelAdmin $model;
    private AuthService $authService;

    public function __construct(PostModelAdmin $model, AuthService $authService)
    {
        $this->model = $model;
        $this->authService = $authService;
    }

    private function parseUserData(array $postData): array
    {
        $postId = $postData['id'] ?? null;
        if (null !== $postId) {
            $postId = filter_var($postId, FILTER_VALIDATE_INT);
            $postId = (false === $postId) ? null : $postId;
        }

        $urlChangeable = $postData['urlChangeable'] ?? null;
        
        $articleType = $postData['articleType'] ?? '';
        $title = trim($postData['title'] ?? '');
        $content = $postData['content'] ?? '';
        $url = transliterate($postData['url'] ?? '');
        $postDate = transliterate($postData['post_date'] ?? '');
        $status = $postData['status'] ?? 'draft';
        $metaTitle = trim($postData['meta_title'] ?? '');
        $metaDescription = trim($postData['meta_description'] ?? '');
        $metaKeywords = trim($postData['meta_keywords'] ?? '');
        $excerpt = trim($postData['excerpt'] ?? '');
        $comment = trim($postData['comment'] ?? '');
        $selectedCategories = $postData['categories'] ?? [];

        $selectedTags = $postData['tags'] ?? [];
        $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

        $thumbnailUrl = trim($postData['post_image_url'] ?? ''); 

        return [
            'postId' => $postId, 'urlChangeable' => $urlChangeable, 
            'articleType' => $articleType, 'title' => $title, 
            'content' => $content, 'url' => $url, 'postDate' => $postDate, 
            'status' => $status, 'metaTitle' => $metaTitle, 
            'metaDescription' => $metaDescription, 'metaKeywords' => $metaKeywords, 
            'excerpt' => $excerpt, 'selectedCategories' => $selectedCategories, 
            'comment' => $comment, 'tagsString' => $tagsString, 
            'thumbnailUrl' => $thumbnailUrl];
    }

    public function createArticle(array $postData, string $articleType): int
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['title' => $title, 
        'content' => $content, 'url' => $url, 'status' => $status,
        'postDate' => $postDate,
        'metaTitle' => $metaTitle, 'metaDescription' => $metaDescription,
        'metaKeywords' => $metaKeywords, 'excerpt' => $excerpt, 
        'selectedCategories' => $selectedCategories, 'comment' => $comment,
        'tagsString' => $tagsString, 'thumbnailUrl' => $thumbnailUrl] = $this->parseUserData($postData);

        $errors=[];
        if (empty($title)) {
            $errors[] = 'Заголовок поста обязателен.';
        }
        if (empty($url)) {
            $errors[] = 'URL поста обязателен.';
        } else if ($this->model->postExists(null, $url, $articleType)) {
            $errors[] = 'Указанный URL уже занят.';
        }

        if (!empty($errors)) {
            throw new UserDataException('Неверно заполнены поля.', $errors, 400);
        }

        $postDate = $postDate ?? date('d-m-Y');
        $mysqlDate = $this->convertToMysqlDate($postDate);
            
        $userId = $this->authService->getUserId();
        $dataForModel = [
            'user_id' => $userId,
            'article_type' => $articleType,
            'status' => $status,
            'postDate' => $mysqlDate,
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'excerpt' => $excerpt,
            'comment' => $comment,
            'thumbnail_url' => $thumbnailUrl,
        ];

        $postId = $this->model->createPost($dataForModel, $selectedCategories, $tagsString);
        
        return $postId;
    }

    public function editArticle(array $postData, string $articleType): void
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['postId' => $postId, 'urlChangeable' => $urlChangeable, 
        'url' => $url, 'title' => $title, 
        'content' => $content, 'status' => $status,
        'postDate' => $postDate,
        'metaTitle' => $metaTitle, 'metaDescription' => $metaDescription,
        'metaKeywords' => $metaKeywords, 'excerpt' => $excerpt, 
        'selectedCategories' => $selectedCategories, 'comment' => $comment,
        'tagsString' => $tagsString, 'thumbnailUrl' => $thumbnailUrl] = $this->parseUserData($postData);

        if (null === $postId)
        {
            throw new UserDataException('Не передан или неверного формата id поста', [], 400);
        }

        $errors=[];
        if (!$this->model->postExists($postId, null, $articleType)) {
            $errors[] = 'Пост не найден.';
        } else if (empty($title)) {
            $errors[] = 'Заголовок поста обязателен.';
        }

        if (!empty($errors)) {
            throw new UserDataException('Неверно заполнены поля.', $errors, 400);
        }

        $postDate = $postDate ?? date('d-m-Y');
        $mysqlDate = $this->convertToMysqlDate($postDate);

        $userId = $this->authService->getUserId();
        $dataForModel = [
            'user_id' => $userId,
            'urlChangeable' => $urlChangeable,
            'url' => $url,
            'article_type' => $articleType,
            'status' => $status,
            'postDate' => $mysqlDate,
            'title' => $title,
            'content' => $content,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'excerpt' => $excerpt,
            'comment' => $comment,
            'thumbnail_url' => $thumbnailUrl,
        ];

        $this->model->updatePost($postId, $dataForModel, $selectedCategories, $tagsString);
    }

    public function deleteArticle(array $postData, string $articleType): bool
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['postId' => $postId] = $this->parseUserData($postData);

        if (null === $postId)
        {
            throw new UserDataException('Не передан или неверного формата id поста', [], 400);
        }

        if (!$this->model->postExists($postId, null, $articleType)) {
            throw new UserDataException('Пост не найден.', [], 404);
        }

        return $this->model->setPostStatus($postId, PostModelAdmin::STATUS_DELETED, 
            $articleType);
    }

    public function checkUrl(array $postData, string $articleType): bool
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['url' => $url] = $this->parseUserData($postData);

        if (null === $url)
        {
            throw new UserDataException('Не передан или неверного формата урл', [], 400);
        }

        return !$this->model->postExists(null, $url, $articleType);
    }

    /**
     * Восстанавливает пост из корзины в статус черновик
     */
    public function restore($postData): bool
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['postId' => $postId, 'articleType' => $articleType] = $this->parseUserData($postData);

        $errors = [];
        if (null === $postId)
        {
            $errors[] = 'Не передан или неверного формата id поста';
        }
        if (empty($articleType))
        {
            $errors[] = 'Не указан тип статьи';
        }
        if (empty($errors)) {
            if (!$this->model->postExists($postId, null, $articleType, PostModelAdmin::STATUS_DELETED)) {
                $errors[] = 'Пост не найден.';
            }
        }

        if (!empty($errors)) {
            throw new UserDataException('Неверно заполнены поля.', $errors, 400);
        }

        return $this->model->setPostStatus($postId, PostModelAdmin::STATUS_DRAFT, $articleType);
    }

    /**
     * Полностью удаляет пост из БД
     */
    public function hardDelete($postData): bool
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['postId' => $postId, 'articleType' => $articleType] = $this->parseUserData($postData);

        $errors = [];
        if (null === $postId)
        {
            $errors[] = 'Не передан или неверного формата id поста';
        }
        if (empty($articleType))
        {
            $errors[] = 'Не указан тип статьи';
        }
        if (empty($errors)) {
            if (!$this->model->postExists($postId, null, $articleType, PostModelAdmin::STATUS_DELETED)) {
                $errors[] = 'Пост не найден.';
            }
        }

        if (!empty($errors)) {
            throw new UserDataException('Неверно заполнены поля.', $errors, 400);
        }

        return $this->model->hardDeletePost($postId, $articleType);
    }

    private function convertToMysqlDate($postDate): string
    {
        // Преобразуем
        $dateTime = DateTime::createFromFormat('d-m-Y', $postDate);

        if ($dateTime) {
            $mysqlDate = $dateTime->format('Y-m-d H:i:s');
        } else {
            // Если формат неправильный, используем текущую дату
            $mysqlDate = date('Y-m-d H:i:s');
        }

        return $mysqlDate;
    }
}