
document.addEventListener('DOMContentLoaded', () => {
    // Существует ли переменная adminRoute вообще?
    // adminRoute определена в шаблоне admin_layout.php
    const route = (typeof adminRoute !== 'undefined') ? `${adminRoute}` : '';

    if (!route) {
        console.error('Ошибка: Переменная adminRoute не определена в шаблоне!');
        // Можно вывести сообщение пользователю, если это критично
        return; 
    }

    // Создаем экземпляр медиатеки
    const myMedia = new MediaLibrary({ adminRoute: `${adminRoute}` });
    const myVideo = new VideoEmbedProcessor();

    // Кнопка для миниатюры поста (вне TinyMCE)
    const openBtn = document.getElementById('openImageModalBtn');
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            myMedia.open((url) => {
                document.getElementById('postImageInput').value = url;
                document.getElementById('postImagePreview').src = url;
                document.getElementById('selectedImagePreview').style.display = 'block';
                document.getElementById('removeImageBtn').style.display = 'block';
            });
        });
    }

    // Если понадобится добавить, например, «Аватар пользователя», не придется переписывать класс MediaLibrary. просто напишем еще один такой кусок:
    // myMedia.open((url) => {
    //     document.getElementById('userAvatar').src = url; // Просто меняем аватарку
    // });

    // Если захотим добавить в пост «Главное видео» (отдельно от текста, как обложку). Благодаря тому, что мы вынесли логику в класс, сможем сделать это так же легко:
    // myVideo.open((html) => {
    //     document.getElementById('mainVideoPreview').innerHTML = html; // Callback для отдельного поля
    // });

    // 3. TinyMCE
    tinymce.init({
        selector: '#postContent',
        plugins: 'link lists code media emoticons wordcount paste autosave',
        toolbar1: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist',
        toolbar2: 'forecolor backcolor | mycustomimage mycustomvideo | link | emoticons | code | wordcount restoredraft',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
        menubar: false,
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        license_key: 'gpl',
        convert_urls: false,
        branding: false,
        sandbox_iframes: false,

        // Настройки автосохранения
        autosave_interval: '10s',      // сохранять каждые 10 секунд
        autosave_retention: '1440m',      // хранить черновик 24 часа
        autosave_prefix: 'tinymce-autosave-{path}{query}-{id}-', // префикс для хранения в браузере
        autosave_restore_when_empty: true, // восстанавливать, если редактор пуст
        autosave_ask_before_unload: true,  // спрашивать при закрытии вкладки
        
        content_style: `
            body { font-family: sans-serif; font-size: 16px; padding: 10px; }
            .video-outer-container { width: 100%; display: block; }
            .video-ratio-box { background: #000; }
        `,
        paste_as_text: true,
        paste_data_images: false, // Чтобы не вставляли картинки мимо медиатеки
        setup: function(editor) {
            editor.ui.registry.addButton('mycustomimage', {
                icon: 'image',
                onAction: () => {
                    myMedia.open((url, alt) => {
                        editor.insertContent(`<img src="${url}" alt="${alt}" decoding="async" loading="lazy" class="img-fluid">`);
                    });
                }
            });

            editor.ui.registry.addButton('mycustomvideo', {
                icon: 'embed',
                tooltip: 'Вставить видео',
                onAction: () => {
                    myVideo.open((html) => {
                        editor.insertContent(html);
                    });
                }
            });

            // Пподменяем стандартное поведение вставки/редактирования изображения на вызов медиатеки.
            editor.addCommand('mceImage', () => {
                myMedia.open((url, alt) => {
                    editor.insertContent(`<img src="${url}" alt="${alt}" decoding="async" loading="lazy" class="img-fluid">`);
                });
            });

            // Пподменяем стандартное поведение вставки/редактирования видео на вызов видеотеки.
            editor.addCommand('mceMedia', () => {
                myVideo.open((html) => {
                    editor.insertContent(html);
                });
            });
        }
    });
});
