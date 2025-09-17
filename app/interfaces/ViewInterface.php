<?php

// app/interfaces/ViewInterface.php

interface ViewInterface {
    public function render(string $template, array $data = []): string;
}