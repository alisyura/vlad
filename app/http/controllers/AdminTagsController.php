<?php
// app/controllers/AdminTagsController.php

class AdminTagsController extends BaseAdminController
{
    private TagsModel $tagsModel;
    private AuthService $authService;
    private PaginationService $paginService;
    private TagService $tagService;

    public function __construct(Request $request, View $view, AuthService $authService, 
        TagsModel $tagsModel, PaginationService $paginService, 
        ResponseFactory $responseFactory, TagService $tagService)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->tagsModel = $tagsModel;
        $this->paginService = $paginService;
        $this->authService = $authService;
        $this->tagService = $tagService;
    }

    public function list($currentPage = 1, $taxonomyType = TaxonomyTypes::TAXONOMY_TAGS): Response
    {
        $userName = $this->authService->getUserName();

        try {
            // Определяем параметры пагинации
            $itemsPerPage = Config::get('admin.TaxonomiesPerPage'); // Количество постов на страницу

            // Базовый URL для админки
            $basePageUrl=$this->getRequest()->getBasePageUrl();

            $paginParams = $this->paginService->calculatePaginationParams($itemsPerPage, $currentPage,
                $this->tagsModel->getTotalTagsCount(), $basePageUrl);
            
            ['totalPages' => $totalPages, 
                'offset' => $offset, 
                'paginationLinks' => $paginationLinks] = $paginParams;

            // Получаем посты для текущей страницы
            $data['taxonomies'] = $this->tagsModel->getTagsWithPostCount($itemsPerPage, $offset);

            $createFormTitle = match($taxonomyType) {
                TaxonomyTypes::TAXONOMY_TAGS => 'Создать новый тэг',
                TaxonomyTypes::TAXONOMY_CATEGORIES => 'Создать новую категорию'
            };
            $createButtonTitle = match($taxonomyType) {
                TaxonomyTypes::TAXONOMY_TAGS => 'Создать тэг',
                TaxonomyTypes::TAXONOMY_CATEGORIES => 'Создать категорию'
            };
            $taxonomyListTitle = match($taxonomyType) {
                TaxonomyTypes::TAXONOMY_TAGS => 'Список тэгов',
                TaxonomyTypes::TAXONOMY_CATEGORIES => 'Список категорий'
            };
            $taxonomyNotFoundMsg = match($taxonomyType) {
                TaxonomyTypes::TAXONOMY_TAGS => 'Тэги не найдены.',
                TaxonomyTypes::TAXONOMY_CATEGORIES => 'Категории не найдены.'
            };

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['active'] = AdminLeftMenuItems::MENU_TAGS; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['createFormTitle'] = $createFormTitle;
            $data['createButtonTitle'] = $createButtonTitle;
            $data['taxonomyListTitle'] = $taxonomyListTitle;
            $data['taxonomyNotFoundMsg'] = $taxonomyNotFoundMsg;
            $data['taxonomyType'] = $taxonomyType;
            $data['pagination'] = [ // Передаем данные для пагинации в представление
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages
                ];
            $data['pagination_links'] = $paginationLinks;
            $data['base_page_url'] = $basePageUrl;
            $data['styles'] = ['taxonomy.css'];
            $data['jss'] = ['taxonomy.js'];
            $data['robotsList'] = [
                    'noindex, follow', 
                    'noindex, nofollow', 
                    'index, follow', 
                    'index, nofollow'
                ];

            return $this->renderHtml('admin/taxonomy/list.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in tags list: ", ['currentPage' => $currentPage], $e);
            throw new HttpException('Произошла непредвиденная ошибка.', 500, $e);
        }
    }

    public function edit(int $tagId, $taxonomyType = TaxonomyTypes::TAXONOMY_TAGS): Response
    {
        $userName = $this->authService->getUserName();

        try {
            $editFormTitle = match($taxonomyType) {
                TaxonomyTypes::TAXONOMY_TAGS => 'Редактирование тэга',
                TaxonomyTypes::TAXONOMY_CATEGORIES => 'Редактирование категории'
            };

            // Добавляем данные для шаблона
            $data['adminRoute'] = $this->getAdminRoute();
            $data['user_name'] = $userName;
            $data['editFormTitle'] = $editFormTitle;
            $data['active'] = AdminLeftMenuItems::MENU_TAGS; // подсветка вкладки левого меню
            $data['isUserAdmin'] = $this->authService->isUserAdmin();
            $data['styles'] = ['taxonomy.css'];
            $data['jss'] = ['taxonomy.js'];
            
            $data['taxonomyToEdit'] = $this->tagService->getTag(id: $tagId);
            $data['taxonomyType'] = $taxonomyType;
            $data['robotsList'] = [
                    'noindex, follow', 
                    'noindex, nofollow', 
                    'index, follow', 
                    'index, nofollow'
                ];

            return $this->renderHtml('admin/taxonomy/edit.php', $data);
        } catch(Throwable $e) {
            Logger::error("Error in edit tag (show form): ", ['tagId' => $tagId], $e);
            if (($e instanceof HttpException) && $e->getCode() == 404)
            {
                throw $e;
            }

            throw new HttpException('Произошла непредвиденная ошибка.', 500);
        }
    }
}