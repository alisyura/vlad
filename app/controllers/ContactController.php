<?php
//declare(strict_types=1);

// app/controllers/TagsController.php

/**
 * Класс ContactController отвечает за обработку формы обратной связи.
 *
 * @property Request $request Объект HTTP-запроса.
 * @property TagsModelClient $model Модель для работы с данными тэгов.
 */
class ContactController extends BaseController
{
    use JsonResponseTrait;
    use ShowClientErrorViewTrait;

    private ContactFormValidator $validator;

    /**
     * Конструктор класса ContactController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param View $view Объект для отображения HTML шаблонов, внедряется через DI-контейнер.
     * @param ContactFormValidator $validator Валидатор, которые проверяет заполненные поля перед отпраывкой сообщения, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, View $view, 
        ContactFormValidator $validator)
    {
        parent::__construct($request, $view);
        $this->validator = $validator;
    }

    /**
     * Отображает страницу "Контакты".
     *
     * Подготавливает данные для шаблона и отображает страницу контактов
     * с помощью объекта ViewAdmin. Обрабатывает возможные исключения.
     *
     * @return void
     */
    public function showKontakty(): void {
        try {
            $URL = $this->request->getBaseUrl();

            $contentData = [
                'full_url' => $this->request->getRequestUrl(),
                'url_id' => 'kontakty',
                'export' => [
                    'page_type' => 'kontakty',
                    'title' => 'Обратная связь | ' . Config::get('global.SITE_NAME'),
                    'site_name' => Config::get('global.SITE_NAME'),
                    'keywords' => Config::get('global.SITE_KEYWORDS'),
                    'description' => Config::get('global.SITE_DESCRIPTION'),
                    'url' => $this->request->getBaseUrl(),
                    'image' => $URL . asset('pic/logo.png'),
                    'robots' => 'noindex, follow',
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
            Logger::error("Error in showKontakty: ", [], $e);
            $this->showErrorView('Ошибка', 'Произошла непредвиденная ошибка.');
        }
        
    }

    /**
     * Отправляет сообщение из контактной формы.
     *
     * Выполняет валидацию данных, полученных из POST-запроса, и отправляет
     * сообщение с помощью сервиса ContactMailerService. Возвращает JSON-ответ
     * с результатом операции.
     *
     * @return void
     */
    public function sendMsg(): void
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

            $errors = $this->validator->validate($data, $file);

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
        } catch(Throwable $e) {
            Logger::error("sendMsg. Ошибка при отправке сообщения.", [], $e);
                
            $this->sendErrorJsonResponse('При отправке сообщения произошла ошибка');
        }
    }
}