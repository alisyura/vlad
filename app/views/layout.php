<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title itemprop="headline"><?= htmlspecialchars($structuredData['site_name']) ?></title>
    <meta itemprop="keywords" name="keywords" content="<?= htmlspecialchars($structuredData['keywords']) ?>">
    <meta itemprop="description" name="description" content="<?= htmlspecialchars($structuredData['description']) ?>">
    <meta name="csrf-token" content="<?= CSRF::getToken() ?>">
    
    <?= generateStructuredData($structuredData); ?>

    <link rel="stylesheet" href="/assets/css/common.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/menu.css">
    <link rel="stylesheet" href="/assets/css/new_pub.css">
    <link rel="stylesheet" href="/assets/css/react.css">
    <link rel="stylesheet" href="/assets/css/modal.css">
    <?php
        switch ($structuredData['page_type']) {
            case 'post':
                $style = 'detail';
                break;
            case 'kontakty':
                $style = 'kontakty';
                break;
            case 'sitemap':
                $style = 'sitemap';
                break;
            case 'tegi':
                $style = 'tegi';
                break;
            default:
                $style = 'list';
        }
    ?>
    <link rel="stylesheet" href="/assets/css/<?= $style ?>.css">
    <meta name="robots" content="noindex, follow">
</head>

<body>
    <div class="container">
        <!-- Левая часть - Меню -->
        <div class="menu">
            <div class="logo-block">
                <div class="logo-site-block">
                    <img class="logo" alt="Логотип" src="/assets/pic/logo.png">
                    <div class="text-block">
                        <img class="site-name" alt="Название сайта" src="/assets/pic/site-name.png">
                        <p class="slogan">портал сатиры, юмора и<br>хорошего настроения</p>
                    </div>
                </div>
                <div class="mobile-menu">
                    <div class="menu-toggle">
                        <div class="hamburger-button"><img src="/assets/pic/hamburger.png" width="30" height="20"></div>
                        <!-- Кнопка гамбургера -->
                        <div class="close-button"><img src="/assets/pic/krestik.png" width="30" height="20"></div>
                        <!-- Кнопка гамбургера -->
                    </div>
                </div>
            </div>

            <div class="menu-block"  itemscope itemtype="http://schema.org/SiteNavigationElement">
                <div class="menu-item">
                    <img src="/assets/pic/menu/glavnaya.png" alt="Главная">
                    <a itemprop="url" href="/">Главная</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/anekdoty.png" alt="Анекдоты">
                    <a itemprop="url" href="/cat/anekdoty">Анекдоты</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/veselaya-rifma.png" alt="Веселая рифма">
                    <a itemprop="url" href="/cat/veselaya-rifma">Веселая рифма</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/citatnik.png" alt="Цитатник">
                    <a itemprop="url" href="/cat/citatnik">Цитатник</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/istorii.png" alt="Истории">
                    <a itemprop="url" href="/cat/istorii">Истории</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/kartinki.png" alt="Картинки">
                    <a itemprop="url" href="/cat/kartinki">Картинки</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/video.png" alt="Видео">
                    <a itemprop="url" href="/cat/video">Видео</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/tegi.png" alt="Тэги">
                    <a itemprop="url" href="/cat/tegi">Тэги</a>
                </div>
                <div class="menu-item">
                    <img src="/assets/pic/menu/luchshee.png" alt="Лучшее">
                    <a itemprop="url" href="/cat/luchshee">Лучшее</a>
                </div>
            </div>

            <a href="#" class="add-button">Добавить</a>

            <div class="links-block">
                <a href="/page/o-proekte.html">О проекте</a>
                <a href="/page/kontakty.html">Контакты</a>
                <a href="/page/policy.html">Пользовательское соглашение</a>

                <div class="sitemap-block">
                    <img class="icon" src="/assets/pic/sitemap.png" alt="Карта сайта"> <!-- Иконка -->
                    <a href="/page/sitemap.html">Карта сайта</a> <!-- Текст -->
                </div>
            </div>
        </div>

        <!-- Средняя часть - Контент -->
        <div class="content">
            <?= $content; ?>
        </div>

        <!-- Правая часть - Реклама -->
        <div class="ad">
            <!-- <div class="ad_block">
                <img src="/assets/pic/ad_block.png">
            </div>

            <div class="ad_block">
                <img src="/assets/pic/ad_block.png">
            </div> -->
        </div>
    </div>


    <!-- Меню -->
    <nav class="navbar">
        <!-- Фоновая плашка меню -->
        <div class="mobilemenu-overlay hidden">
            <!-- Основное меню -->
            <div class="mobilemenu-container">
                <div class="menu-block"itemscope itemtype="http://schema.org/SiteNavigationElement">
                    <div class="menu-item">
                        <img src="/assets/pic/menu/glavnaya.png" alt="Главная">
                        <a itemprop="url" href="/">Главная</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/anekdoty.png" alt="Анекдоты">
                        <a itemprop="url" href="/cat/anekdoty">Анекдоты</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/veselaya-rifma.png" alt="Веселая рифма">
                        <a itemprop="url" href="/cat/veselaya-rifma">Веселая рифма</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/citatnik.png" alt="Цитатник">
                        <a itemprop="url" href="/cat/citatnik">Цитатник</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/istorii.png" alt="Истории">
                        <a itemprop="url" href="/cat/istorii">Истории</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/kartinki.png" alt="Картинки">
                        <a itemprop="url" href="/cat/kartinki">Картинки</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/video.png" alt="Видео">
                        <a itemprop="url" href="/cat/video">Видео</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/tegi.png" alt="Тэги">
                        <a itemprop="url" href="/cat/tegi">Тэги</a>
                    </div>
                    <div class="menu-item">
                        <img src="/assets/pic/menu/luchshee.png" alt="Лучшее">
                        <a itemprop="url" href="/cat/luchshee">Лучшее</a>
                    </div>
                </div>

                <a href="#" class="add-button">Добавить</a>

                <div class="links-block">
                    <a href="/page/o-proekte.html">О проекте</a>
                    <a href="/page/kontakty.html">Контакты</a>
                    <a href="/page/policy.html">Пользовательское соглашение</a>

                    <div class="sitemap-block">
                        <img class="icon" src="/assets/pic/sitemap.png" alt="Карта сайта"> <!-- Иконка -->
                        <a href="/page/sitemap.html">Карта сайта</a> <!-- Текст -->
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Модальное окно -->
    <div class="modal-overlay" id="addPostModal">
        <div class="modal-window">
            <div class="modal-header">
                <h2>Публикация материала</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>

            <!-- Информационный блок -->
            <div class="info-block">
                <div class="icon-container">
                    <!-- Иконка 20x20px -->
                    <img src="/assets/pic/info-icon.png" alt="Инфо" width="20" height="20">
                </div>
                <div class="text-container">
                    <div class="info-title">Уважаемый пользователь!</div>
                    <div class="info-description">Ваша публикация будет опубликована после проверки модератором. Очень просим Вас не присылать материалы непристойного, оскорбляющего, противоречащих нормам и моралям человека. Такие публикации опубликовываться не будут!</div>
                </div>
            </div>

            <!-- Блок email -->
            <!-- <div class="email-block">
                <label for="email" class="email-label">Введите свой Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="Введите e-mail" required>
            </div> -->

            <!-- Поле ввода текста -->
            <div class="text-block">
                <textarea id="post-text" name="post-text" placeholder="Введите текст" maxlength="5000"></textarea>
                <div class="char-counter">0 / 5000</div>
            </div>

            <!-- Поле ввода ссылки на видео -->
            <div class="video-block">
                <input type="url" id="video-link" name="video-link" placeholder="Введите ссылку на видео">
                <!-- Блок подсказки -->
                <div class="video-hint">
                    Допускаются ссылки с сервисов: youtube, vk, rutube, OK, Mail
                </div>
            </div>

            <!-- Блок загрузки файла -->
            <div class="upload-block">
                <div class="upload-content" id="uploadArea">
                    <img src="/assets/pic/upload-icon.png" alt="Загрузка" width="13" height="20">
                    <div class="upload-title" id="uploadTitle">Загрузка файла</div>
                    <div class="upload-description">Нажмите на область или перенесите файл с компьютера</div>
                    <div class="upload-hints">
                        <div>Разрешенные форматы: png, jpeg, jpg, gif</div>
                        <div>Максимальный размер 2 mb</div>
                        <div>Минимальное разрешение 400х300 px</div>
                    </div>
                </div>
                <!-- Скрытое поле для выбора файла -->
                <input type="file" id="file-upload" accept="image/*" style="display: none;">
            </div>

            <!-- Сообщение об ошибке -->
            <div class="form-error" id="formError"></div>

            <!-- Кнопка опубликовать -->
            <div class="publish-button">
                <button id="publishBtn">Опубликовать</button>
            </div>
        </div>
    </div>

    <!-- Всплывающее уведомление -->
    <div id="toast" class="toast hidden">
        <span id="toast-message"></span>
    </div>

    <!-- Скрипты -->
    <script src="/assets/js/menu.js" defer></script>
    <script src="/assets/js/common.js" defer></script>
    <script src="/assets/js/new_pub.js" defer></script>
    <!-- <script src="/assets/js/react.js" defer></script> -->
    <script src="/assets/js/main.js" defer></script>
    <?php
        switch ($structuredData['page_type']) {
            case 'kontakty':
                echo '<script src="/assets/js/kontakty.js" defer></script>'."\n";
                break;
            case 'tegi':
                echo '<script src="/assets/js/tegi.js" defer></script>'."\n";
                break;
            case 'sitemap':
                echo '<script src="/assets/js/sitemap.js" defer></script>'."\n";
                break;
        }
    ?>

    <!-- === Всплывающее уведомление о куках === -->
    <div id="cookie-consent" class="cookie-consent">
        <div class="cookie-text">
            Мы используем cookie на нашем сайте для хранения данных. Продолжая использовать сайт, Вы даете согласие на работу с этими файлами и
            с нашей <a href="/policy.html" target="_blank">Политикой конфиденциальности</a>.
        </div>
        <button id="accept-cookies" class="cookie-btn">Согласиться</button>
    </div>
</body>
</html>