<?php
//declare(strict_types=1);

// app/controllers/TagsController.php

/**
 * Класс TagsController отвечает за обработку AJAX-запросов,
 * связанных с тэгами.
 */
class TagsController extends BaseController
{
    /**
     * @var TagsModelClient Модель для работы с данными тэгов.
     */
    private TagsModelClient $model;

    /**
     * Модель для получения сео настроек
     */
    private SettingsModel $settingModel;

    /**
     * Конструктор класса TagsController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param View $view Объект для отображения HTML шаблонов, внедряется через DI-контейнер.
     * @param TagsModelClient $reactionService Модель для работы с данными тэгов, внедряется через DI-контейнер.
     * @param ResponseFactory $responseFactory Фабрика для создания объектов Response, внедряемая через Dependency Injection.
     */
    public function __construct(Request $request, View $view, 
        TagsModelClient $тagsModelClient, ResponseFactory $responseFactory, 
        SettingsModel $settingModel)
    {
        parent::__construct($request, $view, $responseFactory);
        $this->model = $тagsModelClient;
        $this->settingModel = $settingModel;
    }

    /**
     * Обрабатывает AJAX-запрос на поиск опубликованных тэгов по имени.
     *
     * Получает имя тэга из `$this->request->name`.
     * Возвращает JSON-ответ с результатом поиска или ошибкой.
     *
     * @return void
     */
    public function searchTags(): Response
    {
        $tagName = $this->getRequest()->name;
        try
        {
            $results = $this->model->findPublishedPostTagsByName($tagName);

            return $this->renderJson('Поиск тэгов выполнен успешно', 200, 
                ['tagname' => $tagName, 'tagslist' => $results]);
        }
        catch(Throwable $e)
        {
            Logger::error('Ошибка при поиске тэгов: ', [], $e);
            throw new HttpException('Ошибка при поиске тэгов', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Отображает страницу с результатами поиска тэгов.
     *
     * Получает строку запроса из `$this->request->q`.
     * Если строка запроса не пуста, выполняет поиск тэгов.
     * Рендерит клиентский шаблон 'posts/tegi-seo.php' с данными результатов.
     *
     * @return void
     */
    public function showTagsResults(): Response
    {
        $tagName = $this->getRequest()->q ?? '';

        try
        {
            if (empty($tagName)) {
                $tags = $this->model->findPublishedPostTagsByName('');
                $countTagsToShow = Config::get('posts.count_tags_without_query');
                $tags = array_slice($tags, 0, $countTagsToShow);
            }
            else {
                $tags = $this->model->findPublishedPostTagsByName($tagName);
            }

            $URL = $this->getRequest()->getBaseUrl();

            $seoSettings = $this->settingModel->getMassSeoSettings([
                'index_page_title',
                'index_page_description',
                'index_page_keywords']);

            $contentData = [
                'show_caption' => true,
                'full_url' => $this->getRequest()->getRequestUrl(),
                'tags_baseUrl' => sprintf("%s/tag/", $URL),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                'tags' => $tags,
                'search_tag' => $tagName,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'title' => 'Поиск тэгов | ' . $seoSettings['index_page_title']['value'],
                    'site_name' => $seoSettings['index_page_title']['value'],
                    'keywords' => $seoSettings['index_page_keywords']['value'],
                    'description' => $seoSettings['index_page_description']['value'],
                    'url' => $URL . $this->getRequest()->getUri(),
                    'image' => $URL . asset('pic/logo.png'),
                    'urlTemplate' => sprintf('%s/cat/tegi-results.html?q={search_term_string}', $URL),
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'tegi.css'
                    ],
                    'jss' => [
                        'tegi-seo.js'
                    ]
                ]
            ];

            return $this->renderHtml('posts/tegi-seo.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Ошибка при поиске тэгов", ['tagName' => $tagName], $e);
            throw new HttpException('Ошибка при поиске тэгов', 500, $e);
        }
    }

    /**
     * Отображает страницу с фильтром (формой) для поиска тэгов.
     *
     * Рендерит клиентский шаблон 'posts/tegi.php'.
     *
     * @throws Throwable В случае непредвиденной ошибки во время отображения.
     * @return Response
     */
    public function showTagFilter(): Response {
        $requestUrl = $this->getRequest()->getRequestUrl();

        try {
            $seoSettings = $this->settingModel->getMassSeoSettings([
                'index_page_title',
                'index_page_description',
                'index_page_keywords']);

            $contentData = [
                'show_caption' => true,
                'full_url' => $requestUrl,
                'tags_baseUrl' => sprintf("%s/tag/", $this->getRequest()->getBaseUrl()),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'site_name' => $seoSettings['index_page_title']['value'],
                    'keywords' => $seoSettings['index_page_keywords']['value'],
                    'description' => $seoSettings['index_page_description']['value'],
                    'url' => $requestUrl,
                    'urlTemplate' => sprintf('%s/cat/tegi-results.html?q={search_term_string}', $this->getRequest()->getBaseUrl()),
                    'robots' => 'noindex, follow',
                    'styles' => [
                        'tegi.css'
                    ],
                    'jss' => [
                        'tegi.js'
                    ]
                ]
            ];

            return $this->renderHtml('posts/tegi.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showTagFilter: ", ['requestUrl' => $requestUrl], $e);
            throw new HttpException('Ошибка получения списка тэгов', 500, $e);
        }
    }
}