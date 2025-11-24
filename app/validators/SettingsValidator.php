<?php

// app/validators/SettingsValidator.php

/**
 * Класс-валидатор для создания/изменения настроек.
 */
class SettingsValidator
{
    public function validateCreate(string $key, 
        string $value, 
        ?string $categoryUrl = null, 
        ?string $tagUrl = null): array
    {
        $errors = [];
        if (empty($key))
        {
            $errors[] = 'Ключ не может быть пустым';
        }
        if (empty($value))
        {
            $errors[] = 'Значение не может быть пустым';
        }
        if (!empty($categoryUrl))
        {
            $this->checkKeyFormat($key, $categoryUrl, 'cat', $errors);
        }
        if (!empty($tagUrl))
        {
            $this->checkKeyFormat($key, $tagUrl, 'tag', $errors);
        }

        return $errors;
    }

    public function validateUpdate(int $id, ?string $key, 
        string $value, 
        ?string $categoryUrl = null, 
        ?string $tagUrl = null): array
    {
        $errors = [];
        if ($id < 0)
        {
            $errors[] = 'Некорректное ID';
        }
        if (empty($value))
        {
            $errors[] = 'Значение не может быть пустым';
        }
        if (!empty($key) && !empty($categoryUrl))
        {
            $this->checkKeyFormat($key, $categoryUrl, 'cat', $errors);
        }
        if (!empty($key) && !empty($tagUrl))
        {
            $this->checkKeyFormat($key, $tagUrl, 'tag', $errors);
        }

        return $errors;
    }

    private function checkKeyFormat($key, $url, $prefix, &$errors): void
    {
        // Проверка на 3 части, разделенные '_'
        $parts = array_filter(explode('_', $key));
        $partCount = count($parts);

        if ($partCount < 3) {
            $errors[] = 'Ключ должен состоять минимум из трех частей, разделенных символом "_" (например: cat_url_part2)';
        } else {
            // Проверка, что ключ начинается с "cat_$categoryUrl_"
            $requiredPrefix = "{$prefix}_{$url}_";

            if (!str_starts_with($key, $requiredPrefix)) {
                $errors[] = "Ключ должен начинаться со строки '{$prefix}_{$url}_'";
            }
        }
    }
}