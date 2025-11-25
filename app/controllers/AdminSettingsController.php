<?php
// app/controllers/AdminSettingsController.php

class AdminSettingsController extends BaseAdminController
{
    private SettingsService $settingsService;
    private ListModel $listmodel; 
    private AuthService $authService;

    public function __construct(
        Request $request, 
        View $view, 
        ResponseFactory $responseFactory,
        SettingsService $settingsService,
        ListModel $listmodel, 
        AuthService $authService)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->settingsService = $settingsService;
        $this->listmodel = $listmodel;
        $this->authService = $authService;
    }

    public function list(): Response
    {
        $selectedCategory = $this->getRequest()->category ?? '';
        $selectedTag = $this->getRequest()->tag ?? '';
        $searchQuery = $this->getRequest()->searchquery ?? '';

        try {
            $groupedSettingsList=$this->settingsService->getGroupedSettingsForDisplay(
                $selectedCategory,
                $selectedTag,
                $searchQuery);

            $basePageUrl=$this->getRequest()->getBasePageUrl();

            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'title' => 'Список настроек',
                'active' => "settings", // для подсветки в левом меню
                'isUserAdmin' => $this->authService->isUserAdmin(),
                'groupedSettingsList' => $groupedSettingsList,
                'allowEdit' => $this->authService->isUserAdmin(),
                'basePageUrl' => $basePageUrl,
                'filter' => [
                    'categories' => $this->listmodel->getAllCategories(),
                    'tags' => $this->listmodel->getAllTags(),
                    'selectedCategory' => $selectedCategory,
                    'selectedTag' => $selectedTag,
                    'selectedSearchQuery' => $searchQuery,
                    // При установке фильтра сбрасываем все сортировки и предыдущие фильтры
                    'formAction' => $basePageUrl
                ],
                'styles' => [
                    'settings_list.css',
                ],
                'jss' => [
                    'settings_list.js',
                    'common.js'
                ]
            ];

            return $this->renderHtml('admin/settings/list.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in get settingslist", [
                'selectedCategory' => $selectedCategory, 
                'selectedTag' => $selectedTag,
                'searchQuery' => $searchQuery], 
                $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function create(): Response
    {
        try {
            $data = $this->createRenderData('Создание настройки', 'create');
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function handleCreate(): Response
    {
        $groupName = $this->getRequest()->post('group_name','');
        $groupName = mb_convert_case($groupName, MB_CASE_TITLE, 'UTF-8');
        $key = $this->getRequest()->post('key','');
        $value = $this->getRequest()->post('value','');
        $categoryUrl = $this->getRequest()->post('category_id','');
        $tagUrl = $this->getRequest()->post('tag_id','');
        $comment = $this->getRequest()->post('comment','');

        $adminRoute = $this->getAdminRoute();
        
        try {
            $this->settingsService->createSetting($groupName, $key, $value, 
                $categoryUrl, $tagUrl, $comment);
            
            return $this->redirect("/{$adminRoute}/settings");
        } catch (UserDataException $e) {
            Logger::error("Error in create settingslist", [], $e);

            $data = $this->createRenderData('Создание настройки');
            $newData = [
                'curGroup' => $groupName,
                'curKey' => $key,
                'curValue' => $value,
                'curCategoryUrl' => $categoryUrl,
                'curTagUrl' => $tagUrl,
                'curComment' => $comment,
                'errors' => $e->getValidationErrors()
            ];
            $data = [
                ...$data,
                ...$newData
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);

            $data = $this->createRenderData('Создание настройки');
            $newData = [
                'curGroup' => $groupName,
                'curKey' => $key,
                'curValue' => $value,
                'curCategoryUrl' => $categoryUrl,
                'curTagUrl' => $tagUrl,
                'curComment' => $comment,
                'errors' => [
                    'Сбой при создании настройки'
                ]
            ];
            $data = [
                ...$data,
                ...$newData
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        }
    }

    public function edit(int $id): Response
    {
        try {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new \HttpException("Некорректный формат ID."); 
            }

            $setting = $this->settingsService->getSettingById($id);
            if ($setting === null || empty($setting))
            {
                throw new HttpException('Настройка не найдена');
            }
            $data = $this->createRenderData("Изменение настройки: {$setting['key']}", "edit/{$id}");

            $newData = [
                'builtin' => $setting['builtin'],
                'curGroup' => $setting['group_name'],
                'curKey' => $setting['key'],
                'curValue' => $setting['value'],
                'curCategoryUrl' => $setting['category_url'],
                'curTagUrl' => $setting['tag_url'],
                'curComment' => $setting['comment']
            ];
            $data = [
                ...$data,
                ...$newData
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);
            if ($e instanceof HttpException)
            {
                throw $e;
            }
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function handleEdit($id): Response
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $groupName = $this->getRequest()->post('group_name','');
        $groupName = mb_convert_case($groupName, MB_CASE_TITLE, 'UTF-8');
        $key = $this->getRequest()->post('key');
        $value = $this->getRequest()->post('value','');
        $categoryUrl = $this->getRequest()->post('category_id');
        $tagUrl = $this->getRequest()->post('tag_id');
        $comment = $this->getRequest()->post('comment','');

        $adminRoute = $this->getAdminRoute();
        
        try {
            if ($id === false) {
                throw new \HttpException("Некорректный формат ID."); 
            }
            $setting = $this->settingsService->getSettingById($id);
            if ($setting === null || empty($setting))
            {
                throw new HttpException('Настройка не найдена');
            }

            $this->settingsService->updateSetting($id, $groupName, $key, $value, 
                $categoryUrl, $tagUrl, $comment);
            
            return $this->redirect("/{$adminRoute}/settings");
        } catch (UserDataException $e) {
            Logger::error("Error in create settingslist", [], $e);

            $data = $this->createRenderData("Изменение настройки: {$setting['key']}", "edit/{$id}");
            $newData = [
                'builtin' => $setting['builtin'],
                'curGroup' => $groupName,
                'curKey' => $setting['key'],
                'curValue' => $value,
                'curCategoryUrl' => $setting['category_url'],
                'curTagUrl' => $setting['tag_url'],
                'curComment' => $comment,
                'errors' => $e->getValidationErrors()
            ];
            $data = [
                ...$data,
                ...$newData
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);

            $error = 'Сбой при обновлении настройки';
            if ($e instanceof HttpException || $e instanceof SettingsException)
            {
                $error = $e->getMessage();
            }
            $data = $this->createRenderData("Изменение настройки: {$setting['key']}", "edit/{$id}");
            $newData = [
                'builtin' => $setting['builtin'],
                'curGroup' => $groupName,
                'curKey' => $setting['key'],
                'curValue' => $value,
                'curCategoryUrl' => $setting['category_url'],
                'curTagUrl' => $setting['tag_url'],
                'curComment' => $comment,
                'errors' => [
                    $error
                ]
            ];
            $data = [
                ...$data,
                ...$newData
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        }
    }

    private function createRenderData($title, $formActionSuffix): array
    {
        $adminRoute = $this->getAdminRoute();
        return [
                'title' => $title,
                'builtin' => '0',
                'adminRoute' => $adminRoute,
                'active' => "settings", // для подсветки в левом меню
                'categoriesList' => $this->listmodel->getAllCategories(),
                'tagsList' => $this->listmodel->getAllTags(),
                'existingGroupsList' => $this->settingsService->getExistingGroupNames(),
                'formAction' => "/{$adminRoute}/settings/{$formActionSuffix}",
                'styles' => [
                ],
                'jss' => [
                    'settings_edit_create.js',
                    'common.js'
                ]
            ];
    }
}