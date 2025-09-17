<?php

final class ViewAdmin implements ViewInterface
{
    private string $viewsRootPath;
    private string $loginLayoutPath;
    private string $adminLayoutPath;
    private string $clientLayoutPath;

    public function __construct(string $viewsRootPath, 
        string $loginLayoutPath, string $adminLayoutPath, string $clientLayoutPath)
    {
        $this->viewsRootPath = $viewsRootPath;
        $this->loginLayoutPath = $loginLayoutPath;
        $this->adminLayoutPath = $adminLayoutPath;
        $this->clientLayoutPath = $clientLayoutPath;
    }
    
    public function getViewsRootPath()
    {
        return $this->viewsRootPath;
    }
    /**
     * Renders a specific template and returns its content as a string.
     */
    public function render(string $templatePath, array $data = []): string
    {
        extract($data);
        ob_start();
        include $this->viewsRootPath . '/' . $templatePath;
        return ob_get_clean();
    }

    /**
     * Renders the entire page, including the main content and layout.
     */
    private function renderWithLayout(?string $contentTemplatePath, 
                string $layoutTemplatePath, array $data = []): void
    {
        if (!is_null($contentTemplatePath))
        {
            $content = $this->render($contentTemplatePath, $data);
        }
        $exportData = $data['export'] ?? [];
        if (empty($exportData)) {
            // для админки
            extract($data);
        }
        require $this->viewsRootPath . '/' . $layoutTemplatePath;
    }
    
    /**
     * Renders the login page using the login layout.
     */
    public function renderLogin(array $data = []): void
    {
        $this->renderWithLayout(null, $this->loginLayoutPath, $data);
    }
    
    /**
     * Renders an admin page using the admin layout.
     */
    public function renderAdmin(string $contentView, array $data = []): void
    {
        $this->renderWithLayout($contentView, $this->adminLayoutPath, $data);
    }

    /**
     * Renders an client page using the client layout.
     */
    public function renderClient(string $contentView, array $data = []): void
    {
        $this->renderWithLayout($contentView, $this->clientLayoutPath, $data);
    }
}