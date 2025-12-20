// public/admin/js/edit_create_mediateka.js

/**
 * Класс для управления загрузкой и выбором картинок в TinyMCE
 */
class MediaLibrary {
    constructor(config) {
        this.adminRoute = config.adminRoute || 'admin';
        this.modalElement = document.getElementById('mediaModal');
        this.mediaModal = new bootstrap.Modal(this.modalElement);
        this.mediaGallery = document.getElementById('mediaGallery');
        this.insertMediaBtn = document.getElementById('insertMediaBtn');
        this.uploadForm = document.getElementById('mediaUploadForm');
        
        // Переменные для управления выбором
        this.currentCallback = null;
        this.selectedUrl = null;
        this.selectedAlt = null;

        this.initEventListeners();
    }

    initEventListeners() {
        // Кнопка вставки в модалке
        this.insertMediaBtn.addEventListener('click', () => this.handleInsert());

        // Форма загрузки
        if (this.uploadForm) {
            this.uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }

        // Удаление миниатюры (если элементы есть на странице)
        const removeBtn = document.getElementById('removeImageBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.clearPreview());
        }
    }

    // Открыть медиатеку
    open(callback) {
        this.currentCallback = callback;
        this.resetSelection();
        this.loadItems();
        this.mediaModal.show();
    }

    resetSelection() {
        this.selectedUrl = null;
        this.selectedAlt = null;
        this.insertMediaBtn.disabled = true;
        this.mediaGallery.querySelectorAll('img').forEach(img => img.classList.remove('selected'));
    }

    async loadItems() {
        const url = `/${this.adminRoute}/media/api/list`;
        console.log(url);
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await this.parseResponse(response);

            if (data.success) {
                this.renderGallery(data.mediaList);
            }
        } catch (error) {
            console.error('Ошибка медиатеки:', error);
            alert(error.message);
        }
    }

    renderGallery(items) {
        this.mediaGallery.innerHTML = '';
        items.forEach(item => {
            const col = document.createElement('div');
            col.className = 'col media-item';
            col.innerHTML = `<img src="${item.url}" class="img-thumbnail" alt="${item.alt}" data-url="${item.url}">`;
            
            // Клик (выделение)
            col.addEventListener('click', () => this.selectItem(col.querySelector('img')));
            
            // Двойной клик (сразу вставка)
            col.addEventListener('dblclick', () => {
                this.selectItem(col.querySelector('img'));
                this.handleInsert();
            });

            this.mediaGallery.appendChild(col);
        });
    }

    selectItem(img) {
        this.mediaGallery.querySelectorAll('img').forEach(i => i.classList.remove('selected'));
        img.classList.add('selected');
        this.selectedUrl = img.dataset.url.replace('../../', '/');
        this.selectedAlt = img.alt;
        this.insertMediaBtn.disabled = false;
    }

    handleInsert() {
        if (this.selectedUrl && this.currentCallback) {
            this.currentCallback(this.selectedUrl, this.selectedAlt);
            this.mediaModal.hide();
        }
    }

    async handleUpload(event) {
        event.preventDefault();
        const fileInput = document.getElementById('mediaUpload');
        const altInput = document.getElementById('altText');
        const file = fileInput.files[0];
        const alt = altInput.value;

        if (!file || !alt) return alert('Выберите файл и введите Alt');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('alt', alt);
        formData.append('csrf_token', document.querySelector('meta[name="csrf_token"]')?.content);

        try {
            const response = await fetch(`/${this.adminRoute}/media/api/upload`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await this.parseResponse(response);
            
            if (response.ok) {
                this.uploadForm.reset();
                await this.loadItems();
            } else {
                alert('Ошибка: ' + (data.message || 'Загрузка не удалась'));
            }
        } catch (error) {
            console.error(error);
        }
    }

    async parseResponse(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            if (response.status === 401) window.location.href = `/${this.adminRoute}/login`;
            throw new Error(`Ошибка сервера: ${response.status}`);
        }
    }

    clearPreview() {
        document.getElementById('postImageInput').value = '';
        document.getElementById('postImagePreview').src = '';
        document.getElementById('selectedImagePreview').style.display = 'none';
        document.getElementById('removeImageBtn').style.display = 'none';
    }
}

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
        plugins: 'link  lists code media emoticons wordcount',
        toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link mycustomimage | emoticons | code',
        menubar: false,
        height: 600,
        language: 'ru',
        extended_valid_elements: 'p[class|id|style]',
        valid_elements: '*[*]',
        license_key: 'gpl',
        convert_urls: false,
        branding: false,
        setup: function(editor) {
            editor.ui.registry.addButton('mycustomimage', {
                icon: 'image',
                onAction: () => {
                    myMedia.open((url, alt) => {
                        editor.insertContent(`<img src="${url}" alt="${alt}">`);
                    });
                }
            });

            editor.addCommand('mceImage', () => {
                myMedia.open((url, alt) => {
                    editor.insertContent(`<img src="${url}" alt="${alt}">`);
                });
            });
        }
    });
});
