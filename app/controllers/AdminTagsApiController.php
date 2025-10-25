<?php
// app/controllers/AdminTagsApiController.php

class AdminTagsApiController extends BaseAdminController
{
    use JsonResponseTrait;

    private TagsModel $tagsModel;

    public function __construct(Request $request, TagsModel $tagsModel, ?View $view = null)
    {
        parent::__construct($request, $view);
        $this->tagsModel = $tagsModel;
    }

    /**
     * Поиск тэгов по названию для автодополнения (POST-запрос).
     */
    public function searchTags()
    {
        $query = $this->request->json('q', '');

        if (mb_strlen($query) < 2) {
            $this->sendSuccessJsonResponse('');
            return;
        }

        try {
            $tags = $this->tagsModel->searchTagsByName($query);
            $this->sendSuccessJsonResponse('', 200, ['tags' => $tags]);
        } catch (Throwable $e) {
            $inputJson = $this->request->getJson() ?? [];
            Logger::error('Ошибка при поиске меток: ', $inputJson, $e);
            $this->sendErrorJsonResponse('');
        }

        exit;
    }

    /**
     * @route POST /admin/tags/api/create
     */
    public function create()
    {
        $inputJson = $this->request->getJson();

        // Проверяем наличие необходимых данных
        $requiredFields = ['name', 'url'];
        foreach ($requiredFields as $field) {
            if (empty($inputJson[$field])) {
                $this->sendErrorJsonResponse('Все поля обязательны для заполнения.');
                return;
            }
        }

        try {
            // Проверка уникальности урла
            $checkUniqnessResult = $this->tagsModel->checkTagUniqueness($inputJson['name'], $inputJson['url']);
            if ($checkUniqnessResult['name_exists']) {
                $this->sendErrorJsonResponse('Имя тэга занято.', 409);
                return;
            }
            if ($checkUniqnessResult['url_exists']) {
                $this->sendErrorJsonResponse('Урл тэга занят.', 409);
                return;
            }

            // Попытка создать тэг
            if ($this->tagsModel->createTags([$inputJson])) {
                $this->sendSuccessJsonResponse('Тэг успешно создан.');
            } else {
                $this->sendErrorJsonResponse('Не удалось создать тэг.', 500);
            }
        } catch(Throwable $e) {
            Logger::error('Ошибка при создании тэга: ', $inputJson, $e);
            $this->sendErrorJsonResponse('Сбой при создании тэга', 500);
        }

        exit;
    }

    /**
     * @route PUT /admin/tags/api/edit
     */
    public function edit($tagId)
    {
        $inputJson = $this->request->getJson();

        try {
            $tag = $this->tagsModel->getTag($tagId);
            if (empty($tag))
            {
                $this->sendErrorJsonResponse('Тэг не найден.', 404);
            }

            // Проверяем наличие необходимых данных
            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (empty($inputJson[$field])) {
                    $this->sendErrorJsonResponse('Все поля обязательны для заполнения.');
                    return;
                }
            }

            // Подготовка данных для обновления
            $updateData = [
                'id' => $tagId,
                'name' => $inputJson['name']        
            ];

            // Обновляем данные пользователя в базе данных
            $this->tagsModel->updateTags([$updateData]);

            $this->sendSuccessJsonResponse('Тэг успешно обновлен.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при редактировании тэга: ', $inputJson, $e);
            $this->sendErrorJsonResponse('Сбой при редактировании тэга', 500);
        }

        exit;
    }

    /**
     * @route DELETE /admin/tags/api/block/$userId
     */
    public function delete($tagId)
    {
        try {
            // Обновляем статус пользователя в базе данных
            $this->tagsModel->deleteTags([$tagId]);

            $this->sendSuccessJsonResponse('Тэг успешно удален.');
        } catch(Throwable $e) {
            Logger::error('Ошибка при удалении тэга: ', ['tagId' => $tagId], $e);
            $this->sendErrorJsonResponse('Сбой при удалении тэга', 500);
        }

        exit;
    }
}