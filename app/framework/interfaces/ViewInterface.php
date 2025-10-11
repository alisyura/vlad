<?php

// app/interfaces/ViewInterface.php

interface ViewInterface {
    public function render(string $templatePath, array $data = [], array $headers = [], 
        $httpCode = 200, $replace = true): string;
}