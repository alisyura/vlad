<?php
//declare(strict_types=1);
/**
 * Класс SubmissionController отвечает за обработку AJAX-запросов,
 * связанных с обработкой пользовательский материалов.
 *
 * @property Request $request Объект HTTP-запроса.
 */
class SubmissionController
{
    use JsonResponseTrait;

    private Request $request;
    private SubmissionService $service;

    /**
     * Конструктор класса AjaxController.
     *
     * @param Request $request Объект запроса, внедряется через DI-контейнер.
     * @param SubmissionService $submissionService Сервис, содержащий бизнес-логику по обработке пользовательских материалов, внедряется через DI-контейнер.
     */
    public function __construct(Request $request, SubmissionService $submissionService)
    {
        $this->request = $request;
        $this->service = $submissionService;
    }

    public function publish()
    {
        $content = $this->request->post('text') ?? '';
        $videoLink = $this->request->post('video_link') ?? '';
        $file = $this->request->file('image') ?? null;

        try {
            $this->service->handleSubmission(Config::get('admin.AdminRoleName'),
                $content, $videoLink, $file);

            $this->sendSuccessJsonResponse('Материал успешно отправлен на модерацию');
            exit();

        } catch (Throwable $e) {
            Logger::error("Ошибка при добавлении пользовательского материала",
                 [$e->getTraceAsString()]);
            $this->sendErrorJsonResponse('Произошла ошибка при добавлении материала.', 500);
            exit();
        }
    }
}