<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title itemprop="headline"><?= htmlspecialchars($exportData['title'] ?? '') ?></title>
    <meta itemprop="keywords" name="keywords" content="<?= htmlspecialchars($exportData['keywords'] ?? '') ?>" />
    <meta itemprop="description" name="description" content="<?= htmlspecialchars($exportData['description'] ?? '') ?>" />
    <?php if (!empty($exportData['robots']) && is_string($exportData['robots'])): ?>
        <meta name="robots" content="<?= htmlspecialchars($exportData['robots']) ?>" />
    <?php else: ?>
        <meta name="robots" content="noindex, follow" />
    <?php endif ?>

    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('favicon/favicon-16x16.png') ?>">
    
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon/apple-touch-icon.png') ?>">
    
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('favicon/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= asset('favicon/android-chrome-512x512.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/styles.css" />
    <link rel="stylesheet" href="/assets/css/common.css" />
    <link rel="stylesheet" href="/assets/css/menu.css" />
    <link rel="stylesheet" href="/assets/css/new_pub.css" />
    <link rel="stylesheet" href="/assets/css/react.css" />
    <?php if (!empty($exportData['styles']) && is_array($exportData['styles'])): ?>
        <?php foreach ($exportData['styles'] as $style): ?>
            <link rel="stylesheet" href="<?= asset("css/{$style}") ?>" />
        <?php endforeach ?>
    <?php endif ?>
</head>

<body>
    <div itemprop="publisher" itemscope itemtype="https://schema.org/Organization" class="schema-hidden">
        <meta itemprop="name" content="<?= htmlspecialchars($exportData['site_name'] ?? '') ?>">
        <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
            <meta itemprop="url" content="<?= htmlspecialchars($exportData['image'] ?? '') ?>"> 
        </div>
        <meta itemprop="url" content="<?= htmlspecialchars($exportData['url'] ?? '') ?>"> 
    </div>
    <div class="container">
        <!-- Левая часть - Меню -->
        <nav class="menu">
            <div class="logo-block">
                <div class="logo-site-block">
                    <img class="logo" alt="Логотип" src="/assets/pic/logo.png" />
                    <div class="text-block">
                        <img class="site-name" alt="Название сайта" src="/assets/pic/site-name.png" />
                        <p class="slogan">портал сатиры, юмора и<br />хорошего настроения</p>
                    </div>
                </div>
                <div class="mobile-menu">
                    <div class="menu-toggle">
                        <div class="hamburger-button"><img src="/assets/pic/hamburger.png" width="30" height="20" /></div>
                        <!-- Кнопка гамбургера -->
                        <div class="close-button"><img src="/assets/pic/krestik.png" width="30" height="20" /></div>
                        <!-- Кнопка гамбургера -->
                    </div>
                </div>
            </div>

            <div class="mobilemenu-overlay" id="main-menu-overlay">
            <!-- Основное меню -->
                <div class="mobilemenu-container">
                    <div class="menu-block"itemscope itemtype="http://schema.org/SiteNavigationElement">
                        <div class="menu-item">
                            <img src="/assets/pic/menu/glavnaya.png" alt="Главная" />
                            <a itemprop="url" href="/">Главная</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/anekdoty.png" alt="Анекдоты" />
                            <a itemprop="url" href="/cat/anekdoty">Анекдоты</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/veselaya_rifma.png" alt="Веселая рифма" />
                            <a itemprop="url" href="/cat/veselaya_rifma">Веселая рифма</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/citatnik.png" alt="Цитатник" />
                            <a itemprop="url" href="/cat/citatnik">Цитатник</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/istorii.png" alt="Истории" />
                            <a itemprop="url" href="/cat/istorii">Истории</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/kartinki.png" alt="Картинки" />
                            <a itemprop="url" href="/cat/kartinki">Картинки</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/video.png" alt="Видео" />
                            <a itemprop="url" href="/cat/video">Видео</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/tegi.png" alt="Тэги" />
                            <a itemprop="url" href="/cat/tegi-results.html">Тэги</a>
                        </div>
                        <div class="menu-item">
                            <img src="/assets/pic/menu/luchshee.png" alt="Лучшее" />
                            <a itemprop="url" href="/cat/luchshee">Лучшее</a>
                        </div>
                    </div>

                    <a href="#" class="add-button">Добавить</a>

                    <div class="links-block">
                        <a href="/page/o-proekte.html">О проекте</a>
                        <a href="/page/kontakty.html">Контакты</a>
                        <a href="/page/policy.html">Пользовательское соглашение</a>

                        <div class="sitemap-block">
                            <img class="icon" src="/assets/pic/sitemap.png" alt="Карта сайта" /> <!-- Иконка -->
                            <a href="/page/sitemap.html">Карта сайта</a> <!-- Текст -->
                        </div>
                    </div>

                    <div class="counters-block">
                        <!--LiveInternet counter--><a href="https://www.liveinternet.ru/click"
                        target="_blank"><img id="licntA0E4" width="88" height="31" style="border:0"
                        title="LiveInternet: показано число просмотров за 24 часа, посетителей за 24 часа и за сегодня"
                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAIBTAA7"
                        alt=""/></a><script>(function(d,s){d.getElementById("licntA0E4").src=
                        "https://counter.yadro.ru/hit?t14.4;r"+escape(d.referrer)+
                        ((typeof(s)=="undefined")?"":";s"+s.width+"*"+s.height+"*"+
                        (s.colorDepth?s.colorDepth:s.pixelDepth))+";u"+escape(d.URL)+
                        ";h"+escape(d.title.substring(0,150))+";"+Math.random()})
                        (document,screen)</script><!--/LiveInternet-->

                        <!-- Yandex.Metrika informer -->
                        <a href="https://metrika.yandex.ru/stat/?id=105095209&amp;from=informer" target="_blank" rel="nofollow">
                            <img src="https://informer.yandex.ru/informer/105095209/3_0_FFFFFFFF_EFEFEFFF_0_pageviews"
                                style="width:88px; height:31px; border:0;"
                                alt="Яндекс.Метрика"
                                title="Яндекс.Метрика: данные за сегодня (просмотры, визиты и уникальные посетители)"
                                class="ym-advanced-informer" data-cid="105095209" data-lang="ru"/>
                        </a>
                        <!-- /Yandex.Metrika informer -->

                        <!-- Yandex.Metrika counter -->
                        <script type="text/javascript">
                            (function(m,e,t,r,i,k,a){
                                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                                m[i].l=1*new Date();
                                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
                            })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=105095209', 'ym');

                            ym(105095209, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
                        </script>
                        <noscript><div><img src="https://mc.yandex.ru/watch/105095209" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
                        <!-- /Yandex.Metrika counter -->

                        <br><br>
                    </div>
                </div>
            </div>
        </nav>

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
                    <img src="/assets/pic/info-icon.png" alt="Инфо" width="20" height="20" />
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
                    <img src="/assets/pic/upload-icon.png" alt="Загрузка" width="13" height="20" />
                    <div class="upload-title" id="uploadTitle">Загрузка файла</div>
                    <div class="upload-description">Нажмите на область или перенесите файл с компьютера</div>
                    <div class="upload-hints">
                        <div>Разрешенные форматы: png, jpeg, jpg, gif</div>
                        <div>Максимальный размер 2 mb</div>
                        <div>Минимальное разрешение 400х300 px</div>
                    </div>
                </div>
                <!-- Скрытое поле для выбора файла -->
                <input type="hidden" id="file-upload-max_filesize" value="<?= Config::get('upload.UploadedMaxFilesize') ?>">
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
    <script src="<?= asset("js/menu.js") ?>" defer></script>
    <script src="<?= asset("js/common.js") ?>" defer></script>
    <script src="<?= asset("js/textarea_charcounter.js") ?>" defer></script>
    <script src="<?= asset("js/drag_n_drop_file.js") ?>" defer></script>
    <script src="<?= asset("js/new_pub.js") ?>" defer></script>
    <script src="<?= asset("js/vote_share.js") ?>" defer></script>

    <?php if (!empty($exportData['jss']) && is_array($exportData['jss'])): ?>
        <?php foreach ($exportData['jss'] as $js): ?>
            <script src='<?= asset("js/{$js}") ?>' defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

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