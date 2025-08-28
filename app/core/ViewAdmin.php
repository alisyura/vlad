<?php

final class ViewAdmin
{
    private string $viewsRootPath;
    private string $loginLayoutPath;
    private string $adminLayoutPath;

    public function __construct(string $viewsRootPath, string $loginLayoutPath, string $adminLayoutPath)
    {
        $this->viewsRootPath = $viewsRootPath;
        $this->loginLayoutPath = $loginLayoutPath;
        $this->adminLayoutPath = $adminLayoutPath;
    }
    
    /**
     * Renders a specific template and returns its content as a string.
     */
    private function render(string $templatePath, array $data = []): string
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
        extract($data);
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
}