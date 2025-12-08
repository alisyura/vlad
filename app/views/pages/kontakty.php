<!-- Блок post_preview -->
<div class="contact_full" itemscope itemtype="https://schema.org/ContactPage">
    <!-- Schema.org внутри блока -->
    <meta itemscope itemprop="mainEntityOfPage" itemType="https://schema.org/WebPage" itemid="<?= htmlspecialchars($full_url) ?>"/>
    
    <!-- Заголовок поста -->
    <h1 class="post_preview_header">Обратная связь</h1>

    <!-- Текст поста -->
    <div class="contact_content">
        <div class="contact-form-container">
            <div class="caption">Если у вас возникли проблемы при работе сервиса или Вас интересует другой вопрос, связанный с сервисом, то Вы можете оставить нам сообщение и мы обязательно разберемся в Вашем вопросе.</div>

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
    </div>
</div>