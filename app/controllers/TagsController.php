<?php
//declare(strict_types=1);

// app/controllers/TagsController.php

/**
 * Класс TagsController отвечает за обработку AJAX-запросов,
 * связанных с тэгами.
 *
 * @property Request $request Объект HTTP-запроса.
 * @property TagsModelClient $model Модель для работы с данными тэгов.
 */
class TagsController
{
    use JsonResponseTrait;
    use ShowClientErrorViewTrait;

    private $request;
    private TagsModelClient $model;
    private ViewAdmin $view;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param ViewAdmin $view Объект для отображения HTML шаблонов, внедряется через DI-контейнер.
     * @param TagsModelClient $reactionService Модель для работы с данными тэгов, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, ViewAdmin $view, TagsModelClient $тagsModelClient)
    {
        $this->request = $request;
        $this->model = $тagsModelClient;
        $this->view = $view;
    }

    public function searchTags()
    {
        $tagName = $this->request->name;
        try
        {
            $results = $this->model->findPublishedPostTagsByName($tagName);

            $this->sendSuccessJsonResponse('Поиск тэгов выполнен успешно', 200, 
                ['tagname' => $tagName, 'tagslist' => $results]);
        }
        catch(Exception $e)
        {
            Logger::error('Ошибка при поиске тэгов: ' . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при поиске тэгов');
        }
    }

    /*
    * Страница Тэги
    */
    public function showTagFilter() {
        try {
            $contentData = [
                'show_caption' => true,
                'full_url' => $this->request->requestUrl,
                'tags_baseUrl' => sprintf("%s/tag/", $this->request->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->requestUrl,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
                    'styles' => [
                        'tegi.css'
                    ],
                    'jss' => [
                        'tegi.js'
                    ]
                ]
            ];

            $this->view->renderClient('posts/tegi.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showTagFilter: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
    }
}