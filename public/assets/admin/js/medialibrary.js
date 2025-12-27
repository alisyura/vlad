// public/admin/js/medialibrary.js

/**
 * Класс для управления загрузкой и выбором картинок в TinyMCE
 */
class MediaLibrary {
    constructor(config) {
        this.adminRoute = config.adminRoute;
        this.currentPage = 1;
        this.isAdminMode = config.isAdminMode || false; // Флаг: мы в админке или в TinyMCE

        // Элементы модалки
        this.modalElement = document.getElementById('mediaModal');
        this.mediaModal = this.modalElement ? new bootstrap.Modal(this.modalElement) : null;

        // Универсальные контейнеры: ищем либо админские, либо модальные
        this.mediaGallery = document.getElementById('adminMediaGallery') || document.getElementById('mediaGallery');
        this.paginationContainer = document.getElementById('adminPagination') || document.getElementById('mediaPagination');
        this.uploadForm = document.getElementById('adminUploadForm') || document.getElementById('mediaUploadForm');

        // Кнопки и инпуты
        this.insertMediaBtn = document.getElementById('insertMediaBtn');
        
        this.currentCallback = null;
        this.selectedUrl = null;
        this.selectedAlt = null;

        this.initEventListeners();

        // Если мы в режиме админки, загружаем картинки сразу
        if (this.isAdminMode) {
            this.loadItems(1);
        }
    }

    initEventListeners() {
        // Кнопка вставки (есть только в модалке)
        if (this.insertMediaBtn) {
            this.insertMediaBtn.addEventListener('click', () => this.handleInsert());
        }

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

    // Открыть медиатеку и загрузить первую страницу
    open(callback) {
        this.currentCallback = callback;
        this.resetSelection();
        this.loadItems(1);
        this.mediaModal.show();
    }

    resetSelection() {
        this.selectedUrl = null;
        this.selectedAlt = null;
        this.insertMediaBtn.disabled = true;
        if (this.mediaGallery) {
            this.mediaGallery.querySelectorAll('img').forEach(img => img.classList.remove('selected'));
        }
    }

    async loadItems(page = 1) {
        this.currentPage = page;
        const url = `/${this.adminRoute}/media/api/list?page=${this.currentPage}`;
        console.log(url);
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await this.parseResponse(response);

            if (data.success) {
                this.renderGallery(data.mediaList);
                this.renderPagination(data.totalPages);
            }
        } catch (error) {
            console.error('Ошибка загрузки медиатеки:', error);
            alert(error.message);
        }
    }

    renderGallery(items) {
        this.mediaGallery.innerHTML = '';
        items.forEach(item => {
            const col = document.createElement('div');
            col.className = 'col media-item';
            col.innerHTML = `
                <img src="${item.url}" 
                     class="img-thumbnail" 
                     alt="${item.alt}" 
                     data-url="${item.url}" 
                     style="aspect-ratio: 1/1; object-fit: cover; cursor: pointer;">
            `;
            
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

    renderPagination(totalPages) {
        if (!this.paginationContainer) return;
        this.paginationContainer.innerHTML = '';

        if (totalPages <= 1) return;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm justify-content-center';

        const range = 2; // Сколько страниц показывать по бокам от текущей
        let pages = [];

        // Собираем список номеров страниц, которые точно хотим видеть
        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 || // Первая
                i === totalPages || // Последняя
                (i >= this.currentPage - range && i <= this.currentPage + range) // Соседи
            ) {
                pages.push(i);
            }
        }

        // Отрисовываем кнопки с учетом пропусков (троеточий)
        let lastPage = 0;
        pages.forEach(page => {
            // Если между страницами есть разрыв больше чем в одну цифру — ставим троеточие
            if (lastPage !== 0 && page - lastPage > 1) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">...</span>';
                ul.appendChild(li);
            }

            const li = document.createElement('li');
            li.className = `page-item ${page === this.currentPage ? 'active' : ''}`;
            
            const btn = document.createElement('button');
            btn.className = 'page-link';
            btn.type = 'button';
            btn.innerText = page;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadItems(page);
                document.getElementById('mediaGalleryContainer').scrollTop = 0;
            });

            li.appendChild(btn);
            ul.appendChild(li);
            lastPage = page;
        });

        nav.appendChild(ul);
        this.paginationContainer.appendChild(nav);
    }

    selectItem(img) {
        // Убираем выделение у всех и выделяем текущую
        this.mediaGallery.querySelectorAll('img').forEach(i => i.classList.remove('selected'));
        img.classList.add('selected');
        
        // Сохраняем данные в переменные класса
        this.selectedUrl = img.dataset.url.replace('../../', '/');
        this.selectedAlt = img.alt;
        
        // Если есть кнопка "Вставить" (модалка), активируем её
        if (this.insertMediaBtn) {
            this.insertMediaBtn.disabled = false;
        }

        // Если мы в режиме админки, обновляем правую панель
        if (this.isAdminMode) {
            this.updateDetailsPanel(img);
        }
    }

    updateDetailsPanel(img) {
        const detailsContent = document.getElementById('detailsContent');
        const placeholder = document.getElementById('detailsPlaceholder');
        
        if (!detailsContent || !placeholder) return;

        // Показываем контент, прячем заглушку
        placeholder.style.display = 'none';
        detailsContent.style.display = 'block';

        // Заполняем данные
        document.getElementById('detailPreview').src = img.src;
        document.getElementById('detailPath').value = img.dataset.url;
        document.getElementById('detailAlt').value = img.alt;

        // Вешаем обработчик на кнопку удаления (подготовим его позже)
        const deleteBtn = document.getElementById('deleteMediaBtn');
        if (deleteBtn) {
            deleteBtn.onclick = () => this.handleDelete(img.dataset.url);
        }
    }

    handleInsert() {
        if (this.selectedUrl && this.currentCallback) {
            this.currentCallback(this.selectedUrl, this.selectedAlt);
            // Закрываем модалку только если она существует
            if (this.mediaModal) {
                this.mediaModal.hide();
            }
        }
    }

    async handleUpload(event) {
        event.preventDefault();
        
        // Берем текущую форму, которая вызвала событие
        const form = event.currentTarget;
        // Ищем инпуты внутри этой конкретной формы (а не по всему документу)
        const fileInput = form.querySelector('input[type="file"]');
        const altInput = form.querySelector('input[type="text"]');
        
        const file = fileInput ? fileInput.files[0] : null;
        const alt = altInput ? altInput.value : '';

        if (!file || !alt) return alert('Выберите файл и введите Alt');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('alt', alt);
        
        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

        try {
            const response = await fetch(`/${this.adminRoute}/media/api/upload`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const data = await this.parseResponse(response);
            
            if (response.ok) {
                form.reset(); // Очищаем именно ту форму, которая сработала
                await this.loadItems(1); // Перезагружаем список
            } else {
                alert('Ошибка: ' + (data.message || 'Загрузка не удалась'));
            }
        } catch (error) {
            console.error(error);
            alert('Загрузка не удалась');
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
        const input = document.getElementById('postImageInput');
        const preview = document.getElementById('postImagePreview');
        const container = document.getElementById('selectedImagePreview');
        const removeImageBtn = document.getElementById('removeImageBtn');

        if (input) input.value = '';
        if (preview) preview.src = '';
        if (container) container.style.display = 'none';
        if (removeImageBtn) removeImageBtn.style.display = 'none';
    }
}
