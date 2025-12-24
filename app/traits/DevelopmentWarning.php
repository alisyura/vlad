<?php

// app/traits/DevelopmentWarning.php

namespace App\Traits;

trait DevelopmentWarning
{
    public function __construct()
    {
        trigger_error(
            'Класс ' . __CLASS__ . ' находится в разработке.', 
            E_USER_DEPRECATED
        );
    }
}