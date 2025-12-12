<?php
// app/controllers/AdminCacheApiController.php

class AdminCacheApiController extends BaseAdminController
{
    public function clear(): Response
    {
        try {
            $cacheDir = Config::get('cache.CacheDir');
            if ($cacheDir === null || empty(trim($cacheDir)))
            {
                throw new SettingsException('cache.CacheDir не найдена');
            }

            $cacheDir = rtrim($cacheDir, '/\\');

            // --- ПРОВЕРКА ПРАВ ДОСТУПА ---
            if (!is_dir($cacheDir)) {
                throw new CacheException("Указанный путь к кэшу ({$cacheDir}) не является директорией.");
            }
            if (!is_writable($cacheDir)) {
                // Если мы не можем писать в саму директорию, мы не сможем удалять ее содержимое
                throw new CacheException("Нет прав на запись (удаление) в директории кэша: {$cacheDir}");
            }
            // --- КОНЕЦ ПРОВЕРКИ ПРАВ ДОСТУПА ---

            // Защита: Убедиться, что путь не является корнем системы (дополнительная защита)
            if (in_array($cacheDir, ['/', '\\', 'C:'])) { 
                 throw new CacheException("Попытка очистить корень системы запрещена.");
            }

            $this->deleteDirectoryContents($cacheDir);

            return $this->renderJson('Кэш очищен.');

        } catch (Throwable $e) {
            Logger::error("clear. сбой при очистке кэша", [], $e);
            throw new HttpException('Сбой при при очистке кэша.', 500, $e, HttpException::JSON_RESPONSE);
        }
    }

    /**
     * Приватный метод для рекурсивного удаления содержимого директории.
     * @param string $dir Путь к директории, которую нужно очистить.
     * @throws \RuntimeException Если удаление невозможно.
     */
    private function deleteDirectoryContents(string $dir): void
    {
        if (!($iterator = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS))) {
            throw new \RuntimeException("Не удалось открыть директорию кэша: {$dir}");
        }

        foreach ($iterator as $item) {
            $path = $item->getPathname();

            if ($item->isDir()) {
                // Дополнительная проверка: можем ли мы удалить саму поддиректорию
                if (!is_writable($path)) {
                    throw new \RuntimeException("Нет прав на удаление содержимого поддиректории: {$path}");
                }
                
                // Рекурсивно очищаем, а затем удаляем пустую директорию
                $this->deleteDirectoryContents($path);
                if (!rmdir($path)) {
                    throw new \RuntimeException("Не удалось удалить поддиректорию: {$path}");
                }
            } else {
                // Дополнительная проверка: можем ли мы удалить файл
                if (!is_writable($path)) {
                    throw new \RuntimeException("Нет прав на удаление файла: {$path}");
                }
                
                // Удаляем файл
                if (!unlink($path)) {
                    throw new \RuntimeException("Не удалось удалить файл: {$path}");
                }
            }
        }
    }
}