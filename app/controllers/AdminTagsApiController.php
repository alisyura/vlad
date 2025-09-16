<?php
// app/controllers/AdminTagsApiController.php

class AdminTagsApiController extends BaseController
{
    private TagsModel $tagsModel;

    public function __construct(Request $request, ?ViewAdmin $view = null)
    {
        parent::__construct($request, $view);
        $this->tagsModel = new TagsModel();
    }

    /**
     * Поиск тэгов по названию для автодополнения (POST-запрос).
     */
    public function searchTags()
    {
        header('Content-Type: application/json');

        // Считываем JSON из тела запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $query = $data['q'] ?? '';
        
        if (mb_strlen($query) < 2) {
            echo json_encode([]);
            return;
        }

        try {
            $tags = $this->tagsModel->searchTagsByName($query);
            
            echo json_encode($tags);
        } catch (Exception $e) {
            echo json_encode([]);
            Logger::error('Ошибка при поиске меток: ' . $e->getTraceAsString());
        }
    }

    /**
     * @route POST /admin/tags/api/create
     */
    public function create()
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Получаем JSON-тело запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Проверяем наличие необходимых данных
        $requiredFields = ['name', 'url'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendErrorJsonResponse('Все поля обязательны для заполнения.');
                return;
            }
        }

        // // Проверка уникальности урла
        if ($this->tagsModel->isUrlExists($data['url'])) {
            $this->sendErrorJsonResponse('Урл уже занят.', 409);
            return;
        }

        // Попытка создать тэг
        if ($this->tagsModel->createTags([$data])) {
            $this->sendSuccessJsonResponse('Тэг успешно создан.');
        } else {
            $this->sendErrorJsonResponse('Не удалось создать тэг.', 500);
            
        }
    }

    /**
     * @route PUT /admin/tags/api/edit
     */
    public function edit($tagId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');

        // Получаем JSON-тело запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $tag = $this->tagsModel->getTag($tagId);
        if (empty($tag))
        {
            $this->sendErrorJsonResponse('Тэг не найден.', 404);
        }

        // Проверяем наличие необходимых данных
        $requiredFields = ['name'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendErrorJsonResponse('Все поля обязательны для заполнения.');
            }
        }

        // Подготовка данных для обновления
        $updateData = [
            'id' => $tagId,
            'name' => $data['name']        
        ];

        // Обновляем данные пользователя в базе данных
        $result = $this->tagsModel->updateTags([$updateData]);

        if ($result) {
            $this->sendSuccessJsonResponse('Тэг успешно обновлен.');
        } else {
            $this->sendErrorJsonResponse('Не удалось обновить тэг.', 500);
        }
    }

    /**
     * @route DELETE /admin/tags/api/block/$userId
     */
    public function delete($tagId)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это JSON
        header('Content-Type: application/json');
        
        // Обновляем статус пользователя в базе данных
        $result = $this->tagsModel->deleteTags([$tagId]);

        if ($result) {
            $this->sendSuccessJsonResponse('Тэг успешно удален.');
        } else {
            $this->sendErrorJsonResponse('При удалении тэга произошла ошибка.', 500);
        }
        exit;
    }
}