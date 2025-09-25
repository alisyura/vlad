<?php

// app/validators/ContactFormValidator.php

/**
 * Класс-валидатор для контактной формы.
 *
 * Предоставляет методы для проверки данных, отправленных через контактную форму,
 * включая поля формы и прикрепленный файл.
 */
class ContactFormValidator
{
    /**
     * Валидирует данные и файл, отправленные через контактную форму.
     *
     * Проверяет обязательные поля (email, name, title, text),
     * их формат и длину, а также размер и возможные ошибки загрузки файла.
     *
     * @param array $data Ассоциативный массив с данными формы (email, name, title, text).
     * @param array|null $file Ассоциативный массив с информацией о загруженном файле
     * из суперглобального массива $_FILES, или null, если файл не был прикреплен.
     * @return array Массив строк с сообщениями об ошибках. Если ошибок нет, возвращается пустой массив.
     */
    public function validate(array $data, ?array $file): array
    {
        $errors = [];

        // Проверка email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный адрес электронной почты';
        }

        // Проверка имени
        if (empty($data['name'])) {
            $errors[] = 'Имя не может быть пустым';
        }

        // Проверка темы
        if (empty($data['title'])) {
            $errors[] = 'Тема не может быть пустой';
        }

        // Проверка текста сообщения
        $text_len = mb_strlen($data['text'], 'UTF-8');
        if ($text_len < 10) {
            $errors[] = 'Сообщение должно содержать минимум 10 символов';
        }
        if ($text_len > 5000) {
            $errors[] = 'Сообщение не может превышать 5000 символов';
        }

        // Проверка файла (если он был отправлен)
        if ($file && $file['tmp_name']) {
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    if ($file['size'] > Config::get('upload.UploadedMaxFilesize')) {
                        $errors[] = 'Приложенный файл слишком большого размера';
                    }
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Размер файла превышает лимит сервера';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    break;
                default:
                    $errors[] = 'Произошла неизвестная ошибка при загрузке файла';
            }
        }

        return $errors;
    }
}