class UserDashboard {
    constructor() {
        // Получаем ссылки на DOM-элементы
        this.createUserForm = document.getElementById('create-user-form');
        this.actionLinksContainer = document.querySelector('.table tbody');
        this.actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        this.modalTitle = document.getElementById('actionModalLabel');
        this.modalBody = document.getElementById('actionModalBody');
        this.confirmActionButton = document.getElementById('confirmActionButton');

        // Переменные для хранения данных действия
        this.userIdToActOn = null;
        this.actionToPerform = null;

        // Инициализируем обработчики событий
        this.initEventListeners();
    }

    initEventListeners() {
        if (this.createUserForm) {
            this.createUserForm.querySelector('button[type="button"]').addEventListener('click', this.handleFormSubmit.bind(this));
        }

        if (this.actionLinksContainer) {
            this.actionLinksContainer.addEventListener('click', this.handleActionClick.bind(this));
        }
        
        if (this.confirmActionButton) {
            this.confirmActionButton.addEventListener('click', this.handleModalConfirm.bind(this));
        }
    }

    // Обработка отправки формы
    async handleFormSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.createUserForm);
        const data = Object.fromEntries(formData.entries());

        if (data.password !== data.confirm_password) {
            alert('Пароли не совпадают!');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/users/api/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert('Пользователь успешно создан!');
                this.createUserForm.reset();
                window.location.reload(); 
            } else {
                alert('Ошибка: ' + result.message);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при создании пользователя.');
        }
    }

    // Обработка клика по ссылкам действий
    handleActionClick(e) {
        const link = e.target.closest('.action-link');
        if (!link) return;

        e.preventDefault();
        
        this.userIdToActOn = link.dataset.id;
        this.actionToPerform = link.dataset.action;

        let title = '';
        let bodyText = '';
        let confirmBtnText = '';

        switch (this.actionToPerform) {
            case 'block':
                title = 'Подтвердить блокировку';
                bodyText = 'Вы уверены, что хотите заблокировать этого пользователя?';
                confirmBtnText = 'Заблокировать';
                this.confirmActionButton.className = 'btn btn-danger';
                break;
            case 'unblock':
                title = 'Подтвердить разблокировку';
                bodyText = 'Вы уверены, что хотите разблокировать этого пользователя?';
                confirmBtnText = 'Разблокировать';
                this.confirmActionButton.className = 'btn btn-success';
                break;
            case 'delete':
                title = 'Подтвердить удаление';
                bodyText = 'Вы уверены, что хотите удалить этого пользователя? Это действие необратимо.';
                confirmBtnText = 'Удалить';
                this.confirmActionButton.className = 'btn btn-danger';
                break;
            default:
                return;
        }

        this.modalTitle.textContent = title;
        this.modalBody.textContent = bodyText;
        this.confirmActionButton.textContent = confirmBtnText;

        this.actionModal.show();
    }

    // Обработка подтверждения в модальном окне
    async handleModalConfirm() {
        this.actionModal.hide();
        
        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        let method = '';
        switch (this.actionToPerform) {
            case 'block':
            case 'unblock':
                method = 'PATCH';
                break;
            case 'delete':
                method = 'DELETE';
                break;
            default:
                return;
        }

        try {
            const response = await fetch(`/${adminRoute}/users/api/${this.actionToPerform}/${this.userIdToActOn}`, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
            });

            const result = await response.json();

            if (result.success) {
                alert('Действие выполнено успешно!');
                window.location.reload(); 
            } else {
                alert('Ошибка: ' + result.message);
            }

        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при выполнении действия.');
        } finally {
            this.userIdToActOn = null;
            this.actionToPerform = null;
        }
    }
}

// Создаём и запускаем экземпляр класса при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new UserDashboard();
});