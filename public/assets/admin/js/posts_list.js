// public/admin/js/posts_list.js

/**
 * Класс для управления логикой выбора всех постов.
 */
class PostSelection {
    constructor() {
        this.selectAllDesktop = document.getElementById('select-all-desktop');
        this.postCheckboxes = document.querySelectorAll('input[name="post_ids[]"]');
        this.thrashCheckbox = document.getElementById('thrashbox');
        this.setupEventListeners();
        this.initThrashCheckboxState();
        this.updateSelectAll(); // Инициализация состояния при загрузке страницы
    }

    /**
     * Инициализирует состояние чекбокса "Показать удаленные" на основе URL.
     */
    initThrashCheckboxState() {
        if (!this.thrashCheckbox) return;

        // Проверяем, содержит ли URL-путь '/thrash/'
        this.thrashCheckbox.checked = window.location.pathname.includes('/thrash/');
    }

    /**
     * Обновляет состояние чекбокса "Выбрать все"
     * в зависимости от состояния всех чекбоксов постов.
     */
    updateSelectAll() {
        if (!this.selectAllDesktop) return;

        const allChecked = this.postCheckboxes.length > 0 && 
                           Array.from(this.postCheckboxes).every(cb => cb.checked);
        this.selectAllDesktop.checked = allChecked;
    }

    /**
     * Устанавливает слушатели событий для чекбоксов.
     */
    setupEventListeners() {
        if (this.selectAllDesktop) {
            this.selectAllDesktop.addEventListener('change', () => {
                this.postCheckboxes.forEach(cb => cb.checked = this.selectAllDesktop.checked);
            });
        }

        this.postCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateSelectAll());
        });

        if (this.thrashCheckbox) {
            this.thrashCheckbox.addEventListener('change', this.handleThrashChange.bind(this));
        }
    }

    /**
     * Обрабатывает изменение состояния чекбокса.
     * Изменяет URL-адрес для переключения между обычным списком и списком
     * удаленных элементов.
     *
     * @returns {void}
     */
    handleThrashChange() {
        // Убираем параметры запроса, чтобы не мешали
        const currentPath = window.location.pathname;
        const urlParts = currentPath.split('/').filter(part => part !== ''); // Удаляем пустые строки

        let newPath;

        // Определяем тип ресурса (posts, pages и т.д.), который всегда будет предпоследним элементом
        // если нет пагинации, или третьим с конца, если есть.
        const resource = urlParts[urlParts.length - 1].match(/^p\d+$/)
            ? urlParts[urlParts.length - 2] // Если последний элемент - пагинация, берем предпоследний
            : urlParts[urlParts.length - 1]; // Иначе берем последний

        if (this.thrashCheckbox.checked) {
            // Если сейчас на странице обычных постов, переходим на страницу удаленных
            if (!currentPath.includes('/thrash/')) {
                newPath = `/${urlParts[0]}/thrash/${resource}`;
            }
        } else {
            // Если сейчас на странице удаленных постов, возвращаемся на страницу обычных
            if (currentPath.includes('/thrash/')) {
                newPath = `/${urlParts[0]}/${resource}`;
            }
        }
        
        // Добавляем обратно параметры запроса, если они были
        const queryString = window.location.search;
        if (newPath) {
            window.location.href = newPath + queryString;
        }
    }
}

/**
 * Класс для управления раскрытием/скрытием деталей поста на мобильных устройствах.
 */
class PostDetailsToggle {
    constructor() {
        this.postTitleCells = document.querySelectorAll('.post-title-cell');
        this.setupEventListeners();
    }

    /**
     * Устанавливает слушатели событий для ячеек заголовка поста.
     */
    setupEventListeners() {
        this.postTitleCells.forEach(cell => {
            cell.addEventListener('click', this.handleCellClick.bind(this));
        });
    }

    /**
     * Обработчик клика по ячейке заголовка.
     * @param {Event} event
     */
    handleCellClick(event) {
        // Игнорируем клики по чекбоксу внутри ячейки
        if (event.target.tagName === 'INPUT' && event.target.type === 'checkbox') {
            return; 
        }

        const mobileToggle = event.currentTarget.querySelector('.mobile-details-toggle');
        
        // Проверяем, что мы на мобильном устройстве (d-md-none скрыт)
        if (!mobileToggle || window.getComputedStyle(mobileToggle).display === 'none') {
            return;
        }

        const row = event.currentTarget.closest('.post-row');
        if (!row) return console.error('Родительская строка .post-row НЕ НАЙДЕНА');

        const mobileDetails = row.querySelector('.post-mobile-details');
        const toggleIcon = event.currentTarget.querySelector('.toggle-icon');
        
        if (mobileDetails) {
            mobileDetails.classList.toggle('d-none');
            
            if (toggleIcon) {
                if (mobileDetails.classList.contains('d-none')) {
                    toggleIcon.classList.replace('bi-chevron-up', 'bi-chevron-down');
                } else {
                    toggleIcon.classList.replace('bi-chevron-down', 'bi-chevron-up');
                }
            }
        } else {
            console.error('Блок .post-mobile-details НЕ НАЙДЕН в строке.');
        }
    }
}

/**
 * Класс для управления модальным окном подтверждения удаления поста.
 */
class PostDeleteModal {
    constructor() {
        this.currentPostId = null;
        this.confirmDeleteModal = document.getElementById('confirmDeleteModal');
        this.confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        this.deletePostLinks = document.querySelectorAll('.delete-post-link');
        this.bsModal = null; // Здесь будет храниться экземпляр Bootstrap Modal
        this.setupEventListeners();

        // Инициализация кастомного модального окна
        this.alertModal = document.getElementById('custom-alert-modal');
        this.alertContent = document.getElementById('custom-alert-content');
        this.alertTitleEl = document.getElementById('custom-alert-title');
        this.alertMessageEl = document.getElementById('custom-alert-message');
        this.alertIconContainer = document.getElementById('custom-alert-icon-container');
        this.alertCloseBtn = document.getElementById('custom-alert-close-btn');

        if (this.alertCloseBtn) {
            this.alertCloseBtn.addEventListener('click', () => this.hideAlert());
        }
        if (this.alertModal) {
            this.alertModal.addEventListener('click', (e) => {
                if (e.target === this.alertModal) {
                    this.hideAlert();
                }
            });
        }
    }

    /**
     * Устанавливает слушатели событий для кнопок удаления.
     */
    setupEventListeners() {
        this.deletePostLinks.forEach(link => {
            link.addEventListener('click', this.handleDeleteClick.bind(this));
        });

        if (this.confirmDeleteBtn) {
            this.confirmDeleteBtn.addEventListener('click', this.confirmDelete.bind(this));
        }
    }

    /**
     * Обработчик клика по кнопке удаления.
     * @param {Event} e
     */
    handleDeleteClick(e) {
        e.preventDefault();
        this.currentPostId = e.currentTarget.getAttribute('data-post-id');
        const postTitle = e.currentTarget.getAttribute('data-post-title');

        if (this.confirmDeleteModal) {
            const modalTitle = this.confirmDeleteModal.querySelector('#confirmDeleteModalLabel');
            if (modalTitle) {
                modalTitle.textContent = `Удалить пост: ${postTitle}`;
            }

            // Создаем и сохраняем экземпляр модального окна в свойстве класса
            this.bsModal = new bootstrap.Modal(this.confirmDeleteModal);
            this.bsModal.show();
        }
    }

    /**
     * Обработка подтверждения удаления.
     */
    async confirmDelete() {
        if (!this.currentPostId) return;

        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/posts/delete`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ post_id: this.currentPostId })
            });

            const result = await response.json();
            
            // Используем сохраненный экземпляр для скрытия модального окна
            if (this.bsModal) {
                this.bsModal.hide();
            }

            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = `/${adminRoute}/login`;
                    return;
                }
                throw new Error(result.message);
            }

            if (result.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + (result.message || 'Не удалось удалить пост.'));
            }
        } catch (error) {
            console.error('Ошибка при удалении поста:', error);
            alert('Произошла ошибка при удалении поста.');
        }
    }
}

/**
 * Инициализируем классы, когда DOM-дерево полностью загружено.
 */
document.addEventListener('DOMContentLoaded', () => {
    new PostSelection();
    new PostDetailsToggle();
    new PostDeleteModal();
});
