<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title ?? 'Admin Panel') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('admin/css/admin.css') ?>">
    <?php
        if (!empty($route_path))
        {
            switch ($route_path)
            {
                case 'edit_create':
                    echo '<link rel="stylesheet" href="' . asset("admin/css/edit_create_mediateka.css") .'">'."\n";
                    break;
            }
            echo '<link rel="stylesheet" href="' . asset("admin/css/$route_path.css") .'">'."\n";
        }
    ?>
    <!-- 5555555 -->
    <?php if (!empty($styles) && is_array($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= asset("admin/css/{$style}") ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <meta name="csrf_token" content="<?= htmlspecialchars(CSRF::getToken()) ?>">
</head>
<body>
    <!-- Навигационная панель -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
            <a class="navbar-brand" href="/<?= $adminRoute ?>/dashboard">Админ панель</a>
            <div class="navbar-brand">|</div>
            <a class="navbar-brand" href="/" target="_blank">Сайт</a>

            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 d-none d-sm-inline">
                    Привет, <?= htmlspecialchars($user_name) ?>
                </span>
                <a class="nav-link" href="/<?= $adminRoute ?>/logout">Выйти</a>
            </div>
        </div>
    </nav>

    <!-- Overlay для мобильного меню -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Боковое меню -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/dashboard">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'posts' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/posts">
                                Посты
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'pages' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/pages">
                                Страницы
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'tags' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/tags">
                                Тэги
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'pics' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/pics">
                                Картинки
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" 
                               href="/<?= $adminRoute ?>/settings">
                                Настройки
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Основной контент -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($title ?? 'Панель управления') ?></h1>
                </div>
                
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const adminRoute = '<?= htmlspecialchars(Config::get('admin.AdminRoute')) ?>';
    </script>
    <?php
        if (!empty($route_path))
        {
            switch ($route_path)
            {
                case 'edit_create':
                    echo '<script src="' . asset("tinymce/tinymce.min.js") . '" defer></script>'."\n";
                    echo '<script src="' . asset("admin/js/edit_create_tag_selector.js") . '" defer></script>'."\n";
                    echo '<script src="' . asset("admin/js/edit_create_mediateka.js") . '" defer></script>'."\n";
                    break;
            }
            echo '<script src="' . asset("admin/js/$route_path.js") . '" defer></script>'."\n";
        }
    ?>
    <!-- 7777777 -->
    <?php if (!empty($jss) && is_array($jss)): ?>
        <?php foreach ($jss as $key => $js_file): ?>
            <?php if (strtolower($key) !== 'absolute'): ?>
                <script src="<?= asset("admin/js/$js_file") ?>" defer></script>
            <?php else: ?>
                <script src="<?= asset("$js_file") ?>" defer></script>
            <?php endif ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="<?= asset('js/common.js') ?>" defer></script>
    <!-- Мобильное меню скрипт -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (mobileToggle && sidebar && overlay) {
                // Функция переключения меню
                function toggleMenu() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                    document.body.classList.toggle('menu-open');
                }
                
                // Клик по кнопке гамбургера
                mobileToggle.addEventListener('click', toggleMenu);
                
                // Клик по overlay для закрытия
                overlay.addEventListener('click', toggleMenu);
                
                // Закрыть меню при клике на ссылку на мобильных
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) {
                            sidebar.classList.remove('show');
                            overlay.classList.remove('show');
                            document.body.classList.remove('menu-open');
                        }
                    });
                });
                
                // Закрыть меню при изменении размера экрана
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                        document.body.classList.remove('menu-open');
                    }
                });
            }
        });
    </script>
</body>
</html>