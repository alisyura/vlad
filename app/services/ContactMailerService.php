<?php
// app/services/ContactMailerService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContactMailerService
{
    public function sendContactEmail(array $data, ?array $file): array
    {
        $mail = new PHPMailer(true);

        try {
            // Настройки сервера
            // $mail->isSMTP();
            // $mail->Host = 'smtp.example.com';
            // $mail->SMTPAuth = true;
            // $mail->Username = 'user@example.com';
            // $mail->Password = 'secret';
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            // $mail->Port = 587;

            // От кого
            $mail->setFrom(Config::get('admin.AdminEmail'), 'Сообщение с сайта');
            
            // Кому
            $mail->addAddress(Config::get('admin.AdminEmail'));

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
            Logger::error("ContactMailerService.sendContactEmail. Mailer Error: {$mail->ErrorInfo}", [$e->getTraceAsString()]);
            return ['success' => false, 'message' => $mail->ErrorInfo];
        }
    }
}