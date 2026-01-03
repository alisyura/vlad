<?php

namespace App\Handlers;

use App\Framework\Interfaces\ApiActionHandlerInterface;
use ReflectionClass;

class AutoHandlerDiscovery
{
    /**
     * Находит ВСЕ классы-handlers в указанной папке
     */
    public static function discover(string $handlersDirectory): array
    {
        $handlers = [];

        $pattern = $handlersDirectory . '/*Handler.php';
        $files = glob($pattern);

        foreach ($files as $file) {
            // Из имени файла получаем имя класса
            $className = self::filenameToClassname($file, $handlersDirectory);
            
            if ($className && class_exists($className)) {
                $handlers[] = $className;
            }
        }
        
        return $handlers;
    }

    public static function discoverWithFilter(string $directory): array
    {
        $handlers = [];
        $pattern = $directory . '/*Handler.php';
        $files = glob($pattern);
        
        foreach ($files as $file) {
            $className = self::filenameToClassname($file, $directory);
            
            if (!class_exists($className)) {
                continue;
            }
            
            $reflection = new ReflectionClass($className);
            
            // Только не-абстрактные классы
            if ($reflection->isAbstract()) {
                continue;
            }
            
            // Только реализующие нужный интерфейс
            if (!$reflection->implementsInterface(ApiActionHandlerInterface::class)) {
                continue;
            }
            
            // Только имеющие конструктор без обязательных параметров
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                $params = $constructor->getParameters();
                $hasRequiredParams = array_filter($params, fn($p) => !$p->isDefaultValueAvailable());
                if (count($hasRequiredParams) > 0) {
                    continue; // Пропускаем handlers с обязательными параметрами
                }
            }
            
            $handlers[] = $className;
        }
        
        return $handlers;
    }
    
    /**
     * Преобразует путь к файлу в имя класса
     * Например: /app/Handlers/CreatePaymentHandler.php → App\Handlers\CreatePaymentHandler
     */
    private static function filenameToClassname(string $filepath, string $baseDir): string
    {
        // Убираем базовую директорию и расширение .php
        $relativePath = str_replace([$baseDir, '.php'], '', $filepath);
        
        // Заменяем слэши на обратные (для namespace)
        $relativePath = str_replace('/', '\\', $relativePath);
        
        // Убираем начальный и конечный слэши
        $relativePath = trim($relativePath, '\\');
        
        // Добавляем базовый namespace
        return 'App\\Handlers\\' . $relativePath;
    }
}