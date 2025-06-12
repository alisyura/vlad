<?php

class View {
    public static function render($template, $data = []) {
        extract($data);
        ob_start();
        include $template;
        return ob_get_clean();
    }
}
