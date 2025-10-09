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
        $title = trim($postData['title'] ?? '');
        $content = $postData['content'] ?? '';
        $url = transliterate($postData['url'] ?? '');
        $status = $postData['status'] ?? 'draft';
        $metaTitle = trim($postData['meta_title'] ?? '');
        $metaDescription = trim($postData['meta_description'] ?? '');
        $metaKeywords = trim($postData['meta_keywords'] ?? '');
        $excerpt = trim($postData['excerpt'] ?? '');
        $selectedCategories = $postData['categories'] ?? [];

        $selectedTags = $postData['tags'] ?? [];
        $tagsString = is_array($selectedTags) ? implode(',', $selectedTags) : $selectedTags;

        $thumbnailUrl = trim($postData['post_image_url'] ?? ''); 

        return [
            'title' => $title, 
            'content' => $content, 'url' => $url, 'status' => $status,
            'metaTitle' => $metaTitle, 'metaDescription' => $metaDescription,
            'metaKeywords' => $metaKeywords, 'excerpt' => $excerpt, 
            'selectedCategories' => $selectedCategories,
            'tagsString' => $tagsString, 'thumbnailUrl' => $thumbnailUrl];
    }

    public function createArticle(array $postData, string $articleType): int
    {
        if (empty($postData))
        {
            throw new UserDataException('Данные не переданы', [], 400);
        }

        ['title' => $title, 
        'content' => $content, 'url' => $url, 'status' => $status,
        'metaTitle' => $metaTitle, 'metaDescription' => $metaDescription,
        'metaKeywords' => $metaKeywords, 'excerpt' => $excerpt, 
        'selectedCategories' => $selectedCategories,
        'tagsString' => $tagsString, 'thumbnailUrl' => $thumbnailUrl] = $this->parseUserData($postData);

        $errors=[];
        if (empty($title)) {
            Logger::debug("createPostPost. title empty");
            $errors[] = 'Заголовок поста обязателен.';
        }
        if (empty($url)) {
            Logger::debug("createPostPost. url empty");
            $errors[] = 'URL поста обязателен.';
        } else if ($this->model->postExists(null, $url)) {
            Logger::debug("createPostPost. url exists");
            $errors[] = 'Указанный URL уже занят.';
        }

        if (!empty($errors)) {
            Logger::debug("createPostPost. ошибки заполнены. выход");

            throw new UserDataException('Неверно заполнены поля.', $errors, 400);
        }

            
        $userId = $this->authService->getUserId();
        $dataForModel = [
            'user_id' => $userId,
            'article_type' => $articleType,
            'status' => $status,
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'excerpt' => $excerpt,
            'thumbnail_url' => $thumbnailUrl,
        ];

        $postId = $this->model->createPost($dataForModel, $selectedCategories, $tagsString);
        
        return $postId;
    }
}