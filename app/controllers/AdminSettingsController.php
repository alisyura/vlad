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
        try {
            $groupedSettingsList=$this->settingsService->getGroupedSettingsForDisplay();

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
                    'statuses' => [
                        'Ожидание' => PostModelAdmin::STATUS_PENDING,
                        'Опубликован' => PostModelAdmin::STATUS_PUBLISHED,
                        'Удален' => PostModelAdmin::STATUS_DELETED,
                        'Черновик' => PostModelAdmin::STATUS_DRAFT
                    ],
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
            Logger::error("Error in get settingslist", [], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function create(): Response
    {
        try {
            $data = [
                'adminRoute' => $this->getAdminRoute(),
                'active' => "settings", // для подсветки в левом меню
                'categoriesList' => $this->listmodel->getAllCategories(),
                'tagsList' => $this->listmodel->getAllTags(),
                'existingGroupsList' => $this->settingsService->getExistingGroupNames(),
                'styles' => [
                ],
                'jss' => [
                    'settings_edit_create.js',
                    'common.js'
                ]
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function handleCreate(): Response
    {
        $groupName = $this->getRequest()->post('group_name','');
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
        } catch (Throwable $e) {
            Logger::error("Error in create settingslist", [], $e);

            $data = [
                'curGroup' => $groupName,
                'curKey' => $key,
                'curValue' => $value,
                'curCategoryUrl' => $categoryUrl,
                'curTagUrl' => $tagUrl,
                'curComment' => $comment,
                'errors' => [
                    $e->getMessage()
                ],
                'adminRoute' => $adminRoute,
                'active' => "settings", // для подсветки в левом меню
                'categoriesList' => $this->listmodel->getAllCategories(),
                'tagsList' => $this->listmodel->getAllTags(),
                'existingGroupsList' => $this->settingsService->getExistingGroupNames(),
                'styles' => [
                ],
                'jss' => [
                    'settings_edit_create.js',
                    'common.js'
                ]
            ];
            return $this->renderHtml('admin/settings/edit_create.php', $data);
        }
    }
}