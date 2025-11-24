// public/admin/js/settings_list.js (Очищенная версия)

/**
 * Класс для управления модальным окном подтверждения удаления настроек.
 */
class PostActionsModal {
    constructor() {
        this.currentPostId = null;
        this.currentAction = null;

        this.actionLinks = document.querySelectorAll('.delete-post-link'); 
        this.confirmActionModal = document.getElementById('confirmDeleteModal');
        this.confirmActionBtn = document.getElementById('confirmDeleteBtn');
        this.bsModal = null;
        this.setupEventListeners();
    }

    /**
     * Устанавливает слушатели событий для кнопок действий.
     */
    setupEventListeners() {
        this.actionLinks.forEach(link => {
            link.addEventListener('click', this.handleActionClick.bind(this));
        });
        if (this.confirmActionBtn) {
            this.confirmActionBtn.addEventListener('click', this.confirmAction.bind(this));
        }
    }

    /**
     * Обработчик клика по кнопке действия (удалить).
     * @param {Event} e
     */
    handleActionClick(e) {
        e.preventDefault();
        this.currentPostId = e.currentTarget.getAttribute('data-post-id');
        this.currentAction = e.currentTarget.getAttribute('data-action');
        const postTitle = e.currentTarget.getAttribute('data-post-title');
        
        if (this.confirmActionModal) {
            const modalTitle = this.confirmActionModal.querySelector('#confirmDeleteModalLabel');
            const modalBody = this.confirmActionModal.querySelector('.modal-body');

            if (this.currentAction === 'delete') {
                modalTitle.textContent = `Подтвердите удаление настройки`; // Упрощено
                modalBody.innerHTML = `Вы действительно хотите <b style="color: red;">удалить навсегда</b> настройку: ${postTitle}? Это действие нельзя отменить.`; 
                this.confirmActionBtn.classList.remove('btn-success');
                this.confirmActionBtn.classList.add('btn-danger');
                this.confirmActionBtn.textContent = 'Да, удалить';
            }

            this.bsModal = new bootstrap.Modal(this.confirmActionModal);
            this.bsModal.show();
        }
    }

    /**
     * Обработка подтверждения действия.
     */
    async confirmAction() {
        if (!this.currentPostId) return;

        let url;
        let method = 'DELETE'; // Настройки обычно удаляются навсегда (DELETE)
        
        switch (this.currentAction) {
            case 'delete':
                url = `/${adminRoute}/settings/api/delete`;
                break;
            default:
                console.error('Неизвестное действие');
                return;
        }
        
        const bodyJson = { id: this.currentPostId };
        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(bodyJson)
            });
            if (this.bsModal) {
                this.bsModal.hide();
            }

            const result = await response.json();
            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = `/${adminRoute}/login`;
                    return;
                }
                if (result && result.message) {
                    alert('Ошибка: ' + result.message);
                } else {
                    throw new Error('Не удалось выполнить действие.');
                }
                return;
            }

            if (result.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + (result.message || 'Не удалось выполнить действие.'));
            }
        } catch (error) {
            console.error('Ошибка при выполнении действия:', error);
            alert('Произошла ошибка.');
        }
        
    }
}

/**
 * Класс для управления раскрытием/скрытием деталей настройки на мобильных устройствах.
 */
class PostDetailsToggle {
    constructor() {
        this.postTitleCells = document.querySelectorAll('.post-title-cell');
        this.setupEventListeners();
    }

    /**
     * Устанавливает слушатели событий для ячеек заголовка настройки.
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
        // Игнорируем клики, если это не мобильный режим или клик по действию
        if (event.target.tagName === 'A' || event.target.closest('.post-actions')) {
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
 * Инициализируем классы, когда DOM-дерево полностью загружено.
 */
document.addEventListener('DOMContentLoaded', () => {
    new PostDetailsToggle(); // Для мобильного раскрытия
    new PostActionsModal(); // Для удаления
});