<?php
// app/controllers/AdminTagsApiController.php

class AdminTagsApiController extends BaseAdminController
{
    private TagsModel $tagsModel;
    private TaxonomyService $taxonomyService;
    private RobotsList $robotsList;

    public function __construct(Request $request, TagsModel $tagsModel, 
        ?View $view = null, ResponseFactory $responseFactory,
        TaxonomyService $taxonomyService, RobotsList $robotsList)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->tagsModel = $tagsModel;
        $this->taxonomyService = $taxonomyService;
        $this->robotsList = $robotsList;
    }

    /**
     * Поиск тэгов по названию для автодополнения (POST-запрос).
     */
    public function searchTags(): Response
    {
        $query = $this->getRequest()->json('q', '');

        if (mb_strlen($query) < 2) {
            return $this->renderJson('');
        }

        try {
            $tags = $this->tagsModel->searchTagsByName($query);

            return $this->renderJson('', 200, ['tags' => $tags]);
        } catch (Throwable $e) {
            $inputJson = $this->getRequest()->getJson() ?? [];
            Logger::error('Ошибка при поиске меток: ', $inputJson, $e);
            throw new HttpException('', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route POST /admin/tags/api/create
     * @return Response
     */
    public function create(string $taxonomyType): Response
    {
        $inputJson = $this->getRequest()->getJson();

        // Проверяем наличие необходимых данных
        $requiredFields = ['name', 'url'];
        foreach ($requiredFields as $field) {
            if (empty($inputJson[$field])) {
                throw new HttpException('Все поля обязательны для заполнения.', 400, null, HttpException::JSON_RESPONSE);
            }
        }

        try {
            // Проверка уникальности урла
            $checkUniqnessResult = $this->tagsModel->checkTagUniqueness($inputJson['name'], $inputJson['url']);
            if ($checkUniqnessResult['name_exists']) {
                throw new HttpException('Имя таксономии занято.', 409, null, HttpException::JSON_RESPONSE);
            }
            if ($checkUniqnessResult['url_exists']) {
                throw new HttpException('Урл таксономии занят.', 409, null, HttpException::JSON_RESPONSE);
            }

            $inputJson['robots'] = $inputJson['robots'] ?? 'noindex, follow';
            if (!$this->robotsList->isValid($inputJson['robots']))
            {
                throw new HttpException('Некорректное значение robots.', 409, null, HttpException::JSON_RESPONSE);
            }

            // Попытка создать тэг
            if ($this->taxonomyService->createTags([$inputJson])) {
                return $this->renderJson('Таксономия успешно создана.');
            } else {
                throw new HttpException('Не удалось создать таксономию.', 500);
            }
        } catch(Throwable $e) {
            Logger::error('Ошибка при создании таксономии: ', ['taxonomyType' => $taxonomyType, ...($inputJson)], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Сбой при создании таксономии ' . $taxonomyType, 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route PUT /admin/tags/api/edit
     * @return Response
     */
    public function edit($taxonomyId, string $taxonomyType): Response
    {
        $inputJson = $this->getRequest()->getJson();

        try {
            $tag = $this->taxonomyService->getTag(id: $taxonomyId);
            if (empty($tag))
            {
                throw new HttpException('Таксономия не найдена.', 404, null, HttpException::JSON_RESPONSE);
            }

            // Проверяем наличие необходимых данных
            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (empty($inputJson[$field])) {
                    throw new HttpException('Все поля обязательны для заполнения.', 400, null, HttpException::JSON_RESPONSE);
                }
            }

            $inputJson['robots'] = $inputJson['robots'] ?? 'noindex, follow';
            if (!$this->robotsList->isValid($inputJson['robots']))
            {
                throw new HttpException('Некорректное значение robots.', 409, null, HttpException::JSON_RESPONSE);
            }

            // Подготовка данных для обновления
            $updateData = [
                'id' => $taxonomyId,
                'name' => $inputJson['name'],
                'caption' => $inputJson['caption'],
                'caption_desc' => $inputJson['caption_desc'],
                'title' => $inputJson['title'],
                'description' => $inputJson['description'],
                'keywords' => $inputJson['keywords'],
                'robots' => $inputJson['robots']
            ];

            // Обновляем данные пользователя в базе данных
            $this->taxonomyService->updateTags([$updateData]);

            return $this->renderJson('Таксономия успешно обновлена.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при редактировании таксономии: ', ['taxonomyType' => $taxonomyType, ...($inputJson)], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Сбой при редактировании таксономии ' . $taxonomyType, 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route DELETE /admin/tags/api/block/$userId
     * @return Response
     */
    public function delete($tagId, string $taxonomyType): Response
    {
        try {
            // Обновляем статус пользователя в базе данных
            $this->taxonomyService->deleteTags([$tagId]);

            return $this->renderJson('Таксономия успешно удалена.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при удалении таксономии: ', ['taxonomyType' => $taxonomyType, 'tagId' => $tagId], $e);
            throw new HttpException('Сбой при удалении таксономии' . $taxonomyType, 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}