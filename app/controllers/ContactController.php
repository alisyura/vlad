<?php
//declare(strict_types=1);

// app/controllers/TagsController.php

/**
 * Класс ContactController отвечает за обработку формы обратной связи.
 *
 * @property Request $request Объект HTTP-запроса.
 * @property TagsModelClient $model Модель для работы с данными тэгов.
 */
class ContactController
{
    use JsonResponseTrait;
    use ShowClientErrorViewTrait;

    private $request;
    private ViewAdmin $view;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param ViewAdmin $view Объект для отображения HTML шаблонов, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, ViewAdmin $view)
    {
        $this->request = $request;
        $this->view = $view;
    }

    /*
    * Страница Контакты
    */
    public function showKontakty() {
        try {
            // $URL = rtrim(sprintf("%s/%s", $this->uri, 'page/kontakty'), '/').'.html';
        
            $contentData = [
                //'post' => $page,
                'full_url' => $this->request->requestUrl,
                'url_id' => 'kontakty',
                //'tags_baseUrl' => sprintf("%s/tag/", $this->uri),
                //'post_image' => sprintf("%s%s", $this->uri, $page['image']),
                //'tags' => $tags,
                //'is_post' => false
                'export' => [
                    'page_type' => 'kontakty',
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->requestUrl,
                    //'image' => sprintf("%s%s", $this->uri, $page['image'])
                    'styles' => [
                        'kontakty.css'
                    ],
                    'jss' => [
                        'kontakty.js'
                    ]
                ]
            ];

            $this->view->renderClient('pages/kontakty.php', $contentData);
        } catch (Throwable $e) {
            Logger::error("Error in showKontakty: " . $e->getTraceAsString());
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
        
    }

    public function sendMsg()
    {
        try 
        {
            $data = [
                'name' => trim($this->request->post('name') ?? ''),
                'email' => trim($this->request->post('email') ?? ''),
                'title' => trim($this->request->post('title') ?? ''),
                'text' => trim($this->request->post('text') ?? '')
            ];
            $file = $this->request->file('image') ?? null;

            $validator = new ContactFormValidator();
            $errors = $validator->validate($data, $file);

            if (!empty($errors)) {
                $this->sendErrorJsonResponse($errors);
                return;
            }


            $mailer = new ContactMailerService();
            $result = $mailer->sendContactEmail($data, $file);

            if ($result['success']) {
                $this->sendSuccessJsonResponse('Ваше сообщение успешно отправлено');
            } else {
                $this->sendErrorJsonResponse('Ошибка при отправке сообщения');
            }
        } catch(Exception $e) {
            Logger::error("sendMeg. Ошибка при отправке сообщения.", [$e->getTraceAsString()]);
                
            $this->sendErrorJsonResponse('При отправке сообщения произошла ошибка');
        }
    }
}