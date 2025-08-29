<?php
// app/controllers/BaseController.php

abstract class BaseController {
    protected $viewAdmin;

    public function __construct(ViewAdmin $viewAdmin)
    {
        $this->viewAdmin = $viewAdmin;
    }
}
