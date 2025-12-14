<?php
// app/services/ContactMailerService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Сервис для отправки сообщений по электронной почте.
 *
 * Использует библиотеку PHPMailer для отправки писем, полученных
 * с контактной формы сайта.
 */
class ContactMailerService
{
    /**
     * Отправляет электронное письмо на основе данных из контактной формы.
     *
     * Настраивает и отправляет email-сообщение администратору сайта.
     * Включает имя, email, тему и текст сообщения. Поддерживает прикрепление
     * одного файла.
     *
     * @param array $data Массив с данными формы, должен содержать 'name', 'email', 'title' и 'text'.
     * @param array|null $file Массив с информацией о загруженном файле ($_FILES), или null, если файл отсутствует.
     * @return array Ассоциативный массив с ключом 'success' (true/false) и, в случае ошибки, 'message' с её описанием.
     */
    public function sendContactEmail(array $data, ?array $file): array
    {
        $mail = new PHPMailer(true);

        $host = Config::get('mail.SMTPServer');
        $username = Config::get('mail.MailFrom');
        $password = Config::get('mail.pw');
        $port = Config::get('mail.SMTPPort');
            
        try {
            // Настройки сервера
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;

            // Настройка шифрования и порта
            // Поскольку порт 465, почти наверняка используется SMTPS (SSL/TLS)
            if ($port == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL/TLS
                $mail->Port = 465;
            } else if ($port == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS
                $mail->Port = 587;
            } else {
                $mail->Port = $port;
            }

            // От кого
            $mail->setFrom(Config::get('mail.MailFrom'), 'Сообщение с сайта');
            
            // Кому
            $mail->addAddress(Config::get('mail.AdminEmail'));

            // Установка адреса для ответа
            $mail->addReplyTo($data['email'], $data['name']);

            // Вложения (если есть)
            if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                // Проверка на ошибку загрузки, если она не была сделана раньше
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $mail->addAttachment($file['tmp_name'], $file['name']);
                }
            }
            
            // Content
            $mail->isHTML(false); // Отправляем как обычный текст
            $mail->Subject = "Сообщение с сайта от: " . htmlspecialchars($data['name']);
            $mail->Body = "Имя: {$data['name']}\n";
            $mail->Body .= "Email: {$data['email']}\n";
            $mail->Body .= "Тема: {$data['title']}\n\n";
            $mail->Body .= "Сообщение:\n{$data['text']}\n\n";
            $mail->Body .= "-- Конец сообщения --";

            $mail->send();

            return ['success' => true];

        } catch (Exception $e) {
            Logger::error("ContactMailerService.sendContactEmail. Mailer Error: ", 
                [
                    'host' => $host, 
                    'username' => $username, 
                    'password' => $password, 
                    'port' => $port, 
                    'ErrorInfo' => $mail->ErrorInfo
                ], $e);
            return ['success' => false, 'message' => $mail->ErrorInfo];
        }
    }
}