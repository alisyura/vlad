<?php

// app/services/LlmsService.php

/**
 * Сервис для работы с данными карты сайта и LLMS
 * 
 * Обрабатывает данные, полученные из SitemapModel, группирует их по типам
 * и категориям, подготавливая для вывода в sitemap.xml или llms.txt
 */
class LlmsService
{
    /**
     * Имя файла llms.txt
     */
    public const LLMS_TXT = 'llms.txt';

    /**
     * @var SitemapModel Объект модели для работы с данными карты сайта.
     */
    private SitemapModel $sitemapModel;

    /**
     * Сервис для получения сео настроек
     */
    private SettingsService $settingsService;

    /**
     * Сервис для обработки карты сайта
     */
    private SitemapService $sitemapService;

    /**
     * Объект HTTP запроса
     */
    private Request $request;

     /**
     * Конструктор сервиса
     * 
     * @param SitemapModel $sitemapModel Модель для получения данных
     * @param SettingsService $settingsService Сервис для получения сео настроек
     * @param SitemapService $sitemapService Сервис для обработки карты сайта
     * @param Request $request Объект HTTP запроса
     */
    public function __construct(SitemapModel $sitemapModel, 
        SettingsService $settingsService,
        SitemapService $sitemapService,
        Request $request)
    {
        $this->sitemapModel = $sitemapModel;
        $this->settingsService = $settingsService;
        $this->sitemapService = $sitemapService;
        $this->request = $request;
    }

    private function writeMDheader($fileHandle, array $data)
    {
        fwrite($fileHandle, '# ' . htmlspecialchars($data['title'] ?? '') . "\n\n"); 

        fwrite($fileHandle, "> Last-Modified: ". htmlspecialchars($data['last_modified'] ?? '') . "\n");
        fwrite($fileHandle, ">\n");
        fwrite($fileHandle, "> URL: ". htmlspecialchars($data['base_url'] ?? '') . "\n");
        fwrite($fileHandle, "> ". htmlspecialchars($data['description'] ?? '') . "\n\n");
    }

    private function writeCategorieslist($fileHandle, array $categoriestList, string $baseUrl)
    {
        $url = htmlspecialchars($baseUrl ?? '');
        $catUrls = array_keys($categoriestList);
        $categoryDescriptions = $this->sitemapService->loadCategoryDescriptions($catUrls);

        fwrite($fileHandle, "## Рубрики сайта\n\n");
        fwrite($fileHandle, "- [Главная](". $url . "): Главная страница Смехбука\n");

        foreach($categoriestList as $catUrl => $catName)
        {
            $catDescr = $categoryDescriptions['cat_' . $catUrl . '_caption_desc'];
            $catDescr = get_clean_description($catDescr['value'] ?? null);
            if (empty($catDescr))
            {
                $catDescr = $catName;
            }

            fwrite($fileHandle, sprintf("- [%s](%s/cat/%s): %s\n", htmlspecialchars($catName ?? ''), $url, htmlspecialchars($catUrl ?? ''), htmlspecialchars($catDescr ?? '')));
        }

        fwrite($fileHandle, "\n");
    }

    public function generateLlmsTxt(int $llmsDescriptionLength = 250): array
    {
        $seoSettings = $this->settingsService->getMassSeoSettings([
            'index_page_title',
            'index_page_description']);
        $title = $seoSettings['index_page_title']['value'];
        $description = $seoSettings['index_page_description']['value'];
        if ($this->request->isWeb())
            $baseUrl = $this->request->getBaseUrl();
        else
            $baseUrl = Config::get('cli.siteUrl');

        ['fileHandle' => $fileHandle, 'fullPath' => $fullPath] = 
            $this->createFile();

        $cursor = null;
        try
        {
            $cursor = $this->sitemapModel->getSitemapCursor($llmsDescriptionLength);

            $tempHandle = fopen('php://temp', 'w+');

            $lastModified = null;
            $categoriestList= [];
            $previousType = null;
            while ($row = $cursor->fetch(PDO::FETCH_ASSOC)) {
                $category_url = $row['category_url'];
                if ($row['type'] === 'post' && ($categoriestList[$category_url] ?? null) === null)
                {
                    $categoriestList[$category_url] = $row['category_name'];
                    fwrite($tempHandle, "\n");
                    fwrite($tempHandle, "## " . htmlspecialchars($row['category_name'] ?? '') . "\n");
                }

                if ($row['type'] === 'page' && $previousType === 'post')
                {
                    // начинается вывод простых страниц
                    fwrite($tempHandle, "\n");
                    fwrite($tempHandle, "## Страницы сайта\n");
                }

                $descr = get_clean_description($row['description']);
                if (empty($descr))
                {
                    $descr = $row['post_title'];
                }

                if ($row['type'] === 'post')
                {
                    $lineTemplate = "- [%s](%s/%s.html): %s\n";
                }
                else
                {
                    $lineTemplate = "- [%s](%s/page/%s.html): %s\n";
                }
                fwrite($tempHandle, sprintf($lineTemplate, htmlspecialchars($row['post_title'] ?? ''), htmlspecialchars($baseUrl ?? ''), htmlspecialchars($row['post_url'] ?? ''), htmlspecialchars($descr ?? '')));

                $lastModified = max($lastModified, $row['updated_at']);
                $previousType = $row['type'];
            }

            // 2. Перематываем временный файл в начало
            rewind($tempHandle);

            // 4. Пишем заголовок
            $this->writeMDheader($fileHandle, 
                [
                    'title' => $title, 
                    'last_modified' => date('Y-m-d', strtotime($lastModified)),
                    'base_url' => $baseUrl,
                    'description' => $description
                ]);
            
            $this->writeCategorieslist($fileHandle, $categoriestList, $baseUrl);

            // 5. Копируем всё содержимое временного файла одной командой
            stream_copy_to_stream($tempHandle, $fileHandle, 8192);

            fclose($tempHandle);
            fclose($fileHandle);

            return ['success' => true, 
                    'filePath' => $fullPath,
                    'fileSize' => filesize($fullPath)];
        } catch (Throwable $ex) {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            if ($cursor !== null) {
                $cursor->closeCursor();
            }
            if (file_exists($fullPath)) {
                if (!unlink($fullPath))
                {
                    throw new FilesystemException('Failed to delete file after error', 500, $ex);
                }
            }

            throw $ex;
        }
    }

    /**
     * Проверяет существование файла в корневой директории сайта
     *
     * Выполняет проверку существования файла с валидацией пути для обеспечения безопасности.
     * Метод гарантирует, что проверяемый файл находится в пределах корневой директории,
     * чтобы предотвратить доступ к системным файлам (Directory Traversal Attack).
     *
     * @return array{
     *     success: bool,
     *     filePath?: string,
     *     fileSize?: int|false
     * }
     *         Возвращает массив с результатом проверки:
     *         - 'success' (bool): true если файл существует, доступен для чтения и безопасен,
     *                             false если файл не найден
     *         - 'filePath' (string, опционально): абсолютный путь к файлу (при success = true)
     *         - 'fileSize' (int|false, опционально): размер файла в байтах (при success = true)
     *
     * @throws HttpException Выбрасывается с кодом 403, если путь указывает за пределы корневой директории
     */
    public function checkFileExist(): array
    {
        // Определяем путь к корню
        $rootPath = $this->getPublicDirPath();
        $fullPath = $rootPath . '/' . ltrim(LlmsService::LLMS_TXT, '/');
        
        // Проверка с безопасностью
        $realPath = realpath($fullPath);
        $realRoot = realpath($rootPath);
        
        // Файла нет
        if ($realPath === false) {
            return ['success' => false];
        }
        
        // Проверяем, что файл действительно в корне (безопасность)
        if (strpos($realPath, $realRoot) !== 0) {
            throw new HttpException('Access denied', 403);
        }
        
        if (file_exists($realPath) && is_file($realPath) && is_readable($realPath)) {
            return ['success' => true, 
                'filePath' => $realPath,
                'fileSize' => filesize($realPath)];
        }
        
        // Файл не найден
        return ['success' => false];
    }

    /**
     * Создает файл и возвращает файловый указатель для записи
     *
     * @return resource Ресурс файлового указателя
     * 
     * @throws FilesystemException Выбрасывается при сбое операций с файлом
     */
    private function createFile()
    {
        $rootPath = $this->getPublicDirPath();
        $fullPath = $rootPath . '/' . ltrim(LlmsService::LLMS_TXT, '/');
        
        // Открываем файл для записи (создает если не существует)
        $handle = fopen($fullPath, 'w');
        if ($handle === false) {
            throw new FilesystemException('File creation failure');
        }
        
        // Устанавливаем кодировку UTF-8 для потока
        stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');

        return ['fileHandle' => $handle, 'fullPath' => $fullPath];
    }

    /**
     * Получает полный путь к публичной директории сайта (корень сайта)
     * 
     * @return string Полный физический путь до public
     */
    private function getPublicDirPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [ROOT_PATH, 'public']);
    }
}