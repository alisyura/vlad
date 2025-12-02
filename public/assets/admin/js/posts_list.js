// public/admin/js/posts_list.js

/**
 * Класс для управления модальным окном подтверждения различных действий с постом.
 */
class PostActionsModal {
    constructor() {
        this.currentPostId = null;
        this.currentAction = null;
        this.confirmActionModal = document.getElementById('confirmDeleteModal');
        this.confirmActionBtn = document.getElementById('confirmDeleteBtn');
        this.actionLinks = document.querySelectorAll('.delete-post-link, .restore-post-link');
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
     * Обработчик клика по кнопке действия (удалить, восстановить).
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

            switch (this.currentAction) {
                case 'delete':
                    modalTitle.textContent = `Удалить пост: ${postTitle}`;
                    modalBody.innerHTML = 'Вы действительно хотите <b>переместить</b> этот пост в корзину?';
                    this.confirmActionBtn.classList.remove('btn-success');
                    this.confirmActionBtn.classList.add('btn-danger');
                    this.confirmActionBtn.textContent = 'Да, удалить';
                    break;
                case 'delete-forever':
                    modalTitle.textContent = `Удалить навсегда: ${postTitle}`;
                    modalBody.innerHTML = 'Вы действительно хотите <b style="color: red;">удалить навсегда</b> этот пост? Это действие нельзя отменить.';
                    this.confirmActionBtn.classList.remove('btn-success');
                    this.confirmActionBtn.classList.add('btn-danger');
                    this.confirmActionBtn.textContent = 'Да, удалить навсегда';
                    break;
                case 'restore':
                    modalTitle.textContent = `Восстановить пост: ${postTitle}`;
                    modalBody.innerHTML = 'Вы действительно хотите <b>восстановить</b> этот пост?';
                    this.confirmActionBtn.classList.remove('btn-danger');
                    this.confirmActionBtn.classList.add('btn-success');
                    this.confirmActionBtn.textContent = 'Да, восстановить';
                    break;
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
        let method = 'PATCH';
        let articleType;

        // Выбор URL в зависимости от действия
        switch (this.currentAction) {
            case 'delete':
                articleType = getContentTypeFromUrlRegex(`${adminRoute}`);
                url = `/${adminRoute}/${articleType}s/api/delete`;
                break;
            case 'delete-forever':
                articleType = getThrashContentTypeFromUrlRegex(`${adminRoute}`);
                url = `/${adminRoute}/thrash/api/delete-forever`;
                method = 'DELETE';
                break;
            case 'restore':
                articleType = getThrashContentTypeFromUrlRegex(`${adminRoute}`);
                url = `/${adminRoute}/thrash/api/restore`;
                break;
            default:
                console.error('Неизвестное действие');
                return;
        }

        const bodyJson = { id: this.currentPostId, articleType: articleType };
        
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
                // Проверяем, существует ли result.message и является ли он непустой строкой
                if (result && result.message) {
                    alert('Ошибка: ' + result.message);
                } else {
                    // Если result.message пуст или не существует
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
        if (newPath) {
            window.location.href = newPath;
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

class InitFilters {
    constructor() {
        this.initCalendar();
    }

    initCalendar() {
        flatpickr("#post_date", {
            // настройки flatpickr
            locale: "ru", 
            dateFormat: "d-m-Y", 

            onOpen: (selectedDates, dateStr, instance) => {
                
                // Проверяем, существует ли уже контейнер для кнопок
                if (!instance.calendarContainer.querySelector('.flatpickr-footer-buttons')) {
                    
                    // 1. Создаем общий контейнер для кнопок
                    const footerContainer = document.createElement('div');
                    footerContainer.className = 'flatpickr-footer-buttons d-flex mt-2 gap-2';
                    
                    // 2. Создаем кнопку "СЕГОДНЯ"
                    const todayBtn = document.createElement('button');
                    todayBtn.innerHTML = 'Сегодня';
                    // w-50 делает кнопку в половину ширины, bg-light выделяет ее
                    todayBtn.className = 'flatpickr-today-button btn btn-sm btn-outline-primary w-50';
                    
                    todayBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        // Установка на текущий месяц/год (используем setDate, чтобы выбрать дату)
                        instance.setDate(new Date(), true, instance.config.dateFormat);
                        
                        // NOTE: В отличие от предыдущего кода, setDate с параметром true
                        // автоматически перемещает календарь на нужный месяц
                        // и выбирает дату. 
                    });
                    
                    // 3. Создаем кнопку "СБРОСИТЬ"
                    const clearBtn = document.createElement('button');
                    clearBtn.innerHTML = 'Сбросить';
                    // w-50 делает кнопку в половину ширины, btn-secondary для нейтрального цвета
                    clearBtn.className = 'flatpickr-clear-button btn btn-sm btn-outline-secondary w-50';
                    
                    clearBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        // Метод flatpickr.clear() очищает поле и закрывает календарь
                        instance.clear(); 
                    });
                    
                    // 4. Добавляем кнопки в контейнер
                    footerContainer.appendChild(todayBtn);
                    footerContainer.appendChild(clearBtn);

                    // 5. Находим корневой контейнер календаря и добавляем футер
                    const wrapper = instance.calendarContainer;
                    if (wrapper) {
                        wrapper.appendChild(footerContainer);
                    }
                }
            },
            
            // NOTE: Добавление onClose может быть полезно для сброса фокуса или других действий
            onClose: (selectedDates, dateStr, instance) => {
                // Можно добавить здесь логику, которая срабатывает после выбора даты
            }
        });
    }

}

/**
 * Инициализируем классы, когда DOM-дерево полностью загружено.
 */
document.addEventListener('DOMContentLoaded', () => {
    new PostSelection();
    new PostDetailsToggle();
    new PostActionsModal();
    new InitFilters();
});
