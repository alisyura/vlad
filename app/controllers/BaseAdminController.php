<?php
// app/controllers/BaseAdminController.php

/**
 * Абстрактный базовый класс для контроллеров админки, предоставляющий общие свойства
 * и методы для обработки запросов в приложении.
 *
 * @abstract
 */
abstract class BaseAdminController extends BaseController {
    /**
     * Базовый маршрут (route) для доступа к административной панели.
     * Используется для формирования правильных URL.
     *
     * @var string
     */
    private $adminRoute;

    public function __construct(?Request $request, ?View $view = null)
    {
        parent::__construct($request, $view);
        $this->adminRoute = Config::get('admin.AdminRoute');
    }

    protected function getAdminRoute()
    {
        return $this->adminRoute;
    }
}
