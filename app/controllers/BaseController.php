<?php
// app/controllers/BaseController.php

/**
 * Абстрактный базовый класс для контроллеров, предоставляющий общие свойства
 * и методы для обработки запросов в приложении.
 *
 * @abstract
 * @package App\Controllers
 */
abstract class BaseController {
    /**
     * Объект View для отображения view административной панели.
     *
     * @var ?View
     */
    protected ?View $view;
    /**
     * Объект Request для получения данных из запроса.
     *
     * @var ?Request
     */
    protected ?Request $request;

    public function __construct(?Request $request, ?View $view = null)
    {
        $this->view = $view;
        $this->request = $request;
    }

    protected function getView(): View {
        return $this->view;
    }
}
