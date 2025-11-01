<?php
// app/controllers/AdminTagsApiController.php

class AdminTagsApiController extends BaseAdminController
{
    private TagsModel $tagsModel;

    public function __construct(Request $request, TagsModel $tagsModel, ?View $view = null,
        ResponseFactory $responseFactory)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->tagsModel = $tagsModel;
    }

    /**
     * Поиск тэгов по названию для автодополнения (POST-запрос).
     */
    public function searchTags(): Response
    {
        $query = $this->request->json('q', '');

        if (mb_strlen($query) < 2) {
            return $this->renderJson('');
        }

        try {
            $tags = $this->tagsModel->searchTagsByName($query);

            return $this->renderJson('', 200, ['tags' => $tags]);
        } catch (Throwable $e) {
            $inputJson = $this->request->getJson() ?? [];
            Logger::error('Ошибка при поиске меток: ', $inputJson, $e);
            throw new HttpException('', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route POST /admin/tags/api/create
     * @return Response
     */
    public function create(): Response
    {
        $inputJson = $this->request->getJson();

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
                throw new HttpException('Имя тэга занято.', 409, null, HttpException::JSON_RESPONSE);
            }
            if ($checkUniqnessResult['url_exists']) {
                throw new HttpException('Урл тэга занят.', 409, null, HttpException::JSON_RESPONSE);
            }

            // Попытка создать тэг
            if ($this->tagsModel->createTags([$inputJson])) {
                return $this->renderJson('Тэг успешно создан.');
            } else {
                throw new HttpException('Не удалось создать тэг.', 500);
            }
        } catch(Throwable $e) {
            Logger::error('Ошибка при создании тэга: ', $inputJson, $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Сбой при создании тэга', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route PUT /admin/tags/api/edit
     * @return Response
     */
    public function edit($tagId): Response
    {
        $inputJson = $this->request->getJson();

        try {
            $tag = $this->tagsModel->getTag($tagId);
            if (empty($tag))
            {
                throw new HttpException('Тэг не найден.', 404, null, HttpException::JSON_RESPONSE);
            }

            // Проверяем наличие необходимых данных
            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (empty($inputJson[$field])) {
                    throw new HttpException('Все поля обязательны для заполнения.', 400, null, HttpException::JSON_RESPONSE);
                }
            }

            // Подготовка данных для обновления
            $updateData = [
                'id' => $tagId,
                'name' => $inputJson['name']        
            ];

            // Обновляем данные пользователя в базе данных
            $this->tagsModel->updateTags([$updateData]);

            return $this->renderJson('Тэг успешно обновлен.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при редактировании тэга: ', $inputJson, $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Сбой при редактировании тэга', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * @route DELETE /admin/tags/api/block/$userId
     * @return Response
     */
    public function delete($tagId): Response
    {
        try {
            // Обновляем статус пользователя в базе данных
            $this->tagsModel->deleteTags([$tagId]);

            return $this->renderJson('Тэг успешно удален.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при удалении тэга: ', ['tagId' => $tagId], $e);
            throw new HttpException('Сбой при удалении тэга', 500, $e, HttpException::JSON_RESPONSE);
        }
    }
}