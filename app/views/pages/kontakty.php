<!-- Блок post_preview -->
<article class="post_full" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($full_url) ?>" data-id="<?= htmlspecialchars($url_id) ?>">
    <!-- Schema.org внутри блока -->
    <meta itemprop="headline" content="<?= htmlspecialchars($post['title']) ?>">
    <meta itemprop="description" content="Эскимос шёл за тюленем, но попал к чукче. Теперь он не может найти обратную дорогу.">
    <meta itemprop="url" content="<?= htmlspecialchars($full_url) ?>"> 
    <?php if ($is_post && isset($post_image)): ?>
    <meta itemprop="image" content="<?= $post_image ?>"> 
    <?php endif ?>
    <meta itemprop="datePublished" content="2023-10-12T12:00:00+03:00">
    <meta itemprop="author" content="Автор поста">
    <meta itemprop="publisher" content="Ваш сайт">
    <meta itemprop="keywords" content="анекдоты, чукча, охота, юмор">

    
    <!-- Заголовок поста -->
    <h1 class="post_preview_header" itemprop="headline">Обратная связь</h1>

    <!-- Текст поста -->
    <p class="post_full_text" itemprop="articleBody">
        <div class="contact-form-container">
            <p>Если у вас возникли проблемы при работе сервиса или Вас интересует другой вопрос, связанный с сервисом, то Вы можете оставить нам сообщение и мы обязательно разберемся в Вашем вопросе.</p>

            <div class="form-row">
                <!-- Левый блок -->
                <div class="form-column">
                    <div>Ваше имя</div>
                    <input id="kontaktMsgName" class="inp_text" type="text" placeholder="Как к вам обращаться">
                </div>

                <!-- Правый блок -->
                <div class="form-column">
                    <div>Введите свой e-mail <span class="required">*</span></div>
                    <input id="kontaktMsgEmail" class="inp_text" type="email" placeholder="Введите e-mail">
                </div>
            </div>

            <!-- Тема обращения -->
            <div class="form-section">
                <div>Тема Вашего обращения <span class="required">*</span></div>
                <input id="kontaktMsgTitle" class="inp_text" type="text" placeholder="Введите тему Вашего обращения">
            </div>

            <!-- Текст сообщения -->
            <div class="form-section">
                <div>Текст Вашего сообщения <span class="required">*</span></div>
                <div class="textarea-wrapper">
                    <textarea id="kontaktMsgText" class="inp_text" placeholder="Текст Вашего сообщения"></textarea>
                    <div class="contact-char-counter">0 / 5000</div>
                </div>
            </div>

            <!-- Блок загрузки файла -->
            <div class="contact-upload-block">
                <div class="contact-upload-content" id="contactUploadArea">
                    <img src="/assets/pic/upload-icon.png" alt="Загрузка" width="13" height="20">
                    <div class="contact-upload-title" id="contactUploadTitle">Загрузка файла</div>
                    <div class="contact-upload-description">Нажмите на область или перенесите файл с компьютера</div>
                    <div class="contact-upload-hints">
                        <div>Разрешенные форматы: png, jpeg, jpg, gif</div>
                        <div>Максимальный размер <?= Config::get('upload.UploadedMaxFilesize')/1024/1024 ?> mb</div>
                    </div>
                </div>
                <!-- Скрытое поле для выбора файла -->
                <input type="file" id="contact-file-upload" accept="image/*" style="display: none;">
            </div>

            <!-- Сообщение об ошибке -->
            <div class="contact-form-error" id="contactFormError"></div>

            <!-- Кнопка опубликовать -->
            <div class="send-button">
                <button id="sendBtn">Отправить</button>
            </div>
        </div>
    </p>



    
</article>