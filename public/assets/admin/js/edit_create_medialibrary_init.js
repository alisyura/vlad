
document.addEventListener('DOMContentLoaded', () => {
    // Существует ли переменная adminRoute вообще?
    // adminRoute определена в шаблоне admin_layout.php
    const route = (typeof adminRoute !== 'undefined') ? `${adminRoute}` : '';

    if (!route) {
        console.error('Ошибка: Переменная adminRoute не определена в шаблоне!');
        // Можно вывести сообщение пользователю, если это критично
        return; 
    }

    // 1. Создаем экземпляр медиатеки
    const myMedia = new MediaLibrary({ adminRoute: `${adminRoute}` });

    // 2. Кнопка для миниатюры поста (вне TinyMCE)
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

    // 3. TinyMCE
    tinymce.init({
        selector: '#postContent',
        plugins: 'link lists code media emoticons wordcount paste',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | colors_group | align | bullist numlist | link mycustomimage | emoticons | code',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
        menubar: false,
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        license_key: 'gpl',
        convert_urls: false,
        branding: false,
        paste_as_text: true,
        paste_data_images: false, // Чтобы не вставляли картинки мимо медиатеки
        setup: function(editor) {
            editor.ui.registry.addGroupToolbarButton('colors_group', {
            icon: 'color-picker',
            tooltip: 'Цвета',
            items: 'forecolor backcolor'
            });

            editor.ui.registry.addButton('mycustomimage', {
                icon: 'image',
                onAction: () => {
                    myMedia.open((url, alt) => {
                        editor.insertContent(`<img src="${url}" alt="${alt}" decoding="async" loading="lazy" class="img-fluid">`);
                    });
                }
            });

            editor.addCommand('mceImage', () => {
                myMedia.open((url, alt) => {
                    editor.insertContent(`<img src="${url}" alt="${alt}" decoding="async" loading="lazy" class="img-fluid">`);
                });
            });
        }
    });
});
