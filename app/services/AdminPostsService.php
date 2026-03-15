<?php
// app/services/AdminPostsService.php


class AdminPostsService
{
    private PostModelAdmin $model;

    public function __construct(PostModelAdmin $model)
    {
        $this->model = $model;
    }

    public function getNextId(string $databaseName): int
    {
        $this->validateDatabaseName($databaseName);
        
        try {
            return $this->model->getNextId($databaseName);
        } catch (Exception $e) {
            Logger::error("Failed to get next ID from model", ['databaseName' => $databaseName], $e);
            throw new $e;
        }
    }

    /**
     * Валидирует имя базы данных
     * 
     * @param string $databaseName
     * @throws InvalidArgumentException
     */
    private function validateDatabaseName(string $databaseName): void
    {
        if (empty(trim($databaseName))) {
            throw new InvalidArgumentException('Database name cannot be empty');
        }
        
        // Дополнительные проверки, если нужно
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
            throw new InvalidArgumentException('Database name contains invalid characters');
        }
    }
}