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

    /**
     * @var Request Объект HTTP-запроса.
     */
    private Request $request;

    /**
     * @var TagsModelClient Модель для работы с данными тэгов.
     */
    private TagsModelClient $model;

    /**
     * @var ViewAdmin Объект для отображения HTML-шаблонов.
     */
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

    /**
     * Обрабатывает AJAX-запрос на поиск опубликованных тэгов по имени.
     *
     * Получает имя тэга из `$this->request->name`.
     * Возвращает JSON-ответ с результатом поиска или ошибкой.
     *
     * @return void
     */
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

    /**
     * Отображает страницу с результатами поиска тэгов.
     *
     * Получает строку запроса из `$this->request->q`.
     * Если строка запроса не пуста, выполняет поиск тэгов.
     * Рендерит клиентский шаблон 'posts/tegi-seo.php' с данными результатов.
     *
     * @return void
     */
    public function showTagsResults()
    {
        $tagName = $this->request->q;

        try
        {
            if (empty($tagName)) {
                $tags = $this->model->findPublishedPostTagsByName('');
                $tags = array_slice($tags, 0, 10);
            }
            else {
                $tags = $this->model->findPublishedPostTagsByName($tagName);
            }

            $URL = rtrim(sprintf("%s", $this->request->getBaseUrl()), '/');

            $contentData = [
                'show_caption' => true,
                'full_url' => $this->request->getRequestUrl(),
                'tags_baseUrl' => sprintf("%s/tag/", $URL),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                'tags' => $tags,
                'search_tag' => $tagName,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'title' => 'Поиск тэгов | ' . Config::get('global.SITE_NAME'),
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $URL . $this->request->getUri(),
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

            $this->view->renderClient('posts/tegi-seo.php', $contentData);
        }
        catch(Exception $e)
        {
            Logger::error('Ошибка при поиске тэгов: ' . $e->getTraceAsString());
            $this->sendErrorJsonResponse('Ошибка при поиске тэгов');
        }
    }

    /**
     * Отображает страницу с фильтром (формой) для поиска тэгов.
     *
     * Рендерит клиентский шаблон 'posts/tegi.php'.
     *
     * @throws Throwable В случае непредвиденной ошибки во время отображения.
     * @return void
     */
    public function showTagFilter() {
        try {
            $contentData = [
                'show_caption' => true,
                'full_url' => $this->request->getRequestUrl(),
                'tags_baseUrl' => sprintf("%s/tag/", $this->request->getBaseUrl()),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                'is_post' => false,
                'export' => [
                    'page_type' => 'tegi',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->getRequestUrl(),
                    'urlTemplate' => sprintf('%s/cat/tegi-results.html?q={search_term_string}', $this->request->getBaseUrl()),
                    'robots' => 'noindex, follow',
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