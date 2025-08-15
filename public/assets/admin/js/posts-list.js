// public/admin/js/posts-list-interactions.js

document.addEventListener('DOMContentLoaded', function () {
    // --- Логика для чекбокса "Выбрать все" ---
    const selectAllDesktop = document.getElementById('select-all-desktop');
    const postCheckboxes = document.querySelectorAll('input[name="post_ids[]"]');

    function updateSelectAll() {
        if (selectAllDesktop) {
            selectAllDesktop.checked = postCheckboxes.length > 0 && Array.from(postCheckboxes).every(cb => cb.checked);
        }
    }

    if (selectAllDesktop) {
        selectAllDesktop.addEventListener('change', function () {
            postCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectAll();
        });
    }

    postCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAll);
    });

    updateSelectAll(); // Инициализация состояния при загрузке страницы


    // --- Логика раскрытия/скрытия деталей на мобильных ---
    // Теперь слушаем клики по всей ячейке заголовка (.post-title-cell) на мобильных
    const postTitleCells = document.querySelectorAll('.post-title-cell');

    postTitleCells.forEach(cell => {
        // Добавляем слушатель кликов на всю ячейку
        cell.addEventListener('click', function (event) {
            // Игнорируем клики по чекбоксу внутри ячейки
            if (event.target.tagName === 'INPUT' && event.target.type === 'checkbox') {
                return; 
            }

            // Проверяем, что мы находимся на мобильном устройстве (d-md-none виден)
            // Это важно, чтобы на десктопе не срабатывала логика раскрытия/скрытия
            const mobileToggle = this.querySelector('.mobile-details-toggle');
            if (!mobileToggle || window.getComputedStyle(mobileToggle).display === 'none') {
                 return; // Если mobile-details-toggle скрыт (т.е. мы на десктопе), выходим
            }

            console.log('Клик по ячейке заголовка на мобильном:', this);
            const row = this.closest('.post-row'); // Находим родительскую строку <tr>
            
            if (row) {
                console.log('Найдена родительская строка:', row); 
                const mobileDetails = row.querySelector('.post-mobile-details'); 
                const toggleIcon = this.querySelector('.toggle-icon'); 
                
                if (mobileDetails) {
                    console.log('Найден блок деталей:', mobileDetails); 
                    console.log('До клика: mobileDetails имеет класс d-none?', mobileDetails.classList.contains('d-none'));

                    // Переключаем класс 'd-none'
                    mobileDetails.classList.toggle('d-none'); 

                    console.log('После клика: mobileDetails имеет класс d-none?', mobileDetails.classList.contains('d-none'));

                    // Меняем иконку
                    if (mobileDetails.classList.contains('d-none')) {
                        // Блок скрыт, показываем стрелку вниз
                        if (toggleIcon) { // Проверяем, что иконка найдена
                            toggleIcon.classList.remove('bi-chevron-up');
                            toggleIcon.classList.add('bi-chevron-down');
                        }
                    } else {
                        // Блок виден, показываем стрелку вверх
                        if (toggleIcon) { // Проверяем, что иконка найдена
                            toggleIcon.classList.remove('bi-chevron-down');
                            toggleIcon.classList.add('bi-chevron-up');
                        }
                    }
                } else {
                    console.error('Блок .post-mobile-details НЕ НАЙДЕН в строке:', row);
                }
            } else {
                console.error('Родительская строка .post-row НЕ НАЙДЕНА для кнопки:', this);
            }
        });
    });

    // --- Модальное окно подтверждения удаления ---
    let currentPostId = null;
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deletePostLinks = document.querySelectorAll('.delete-post-link');

    deletePostLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            currentPostId = this.getAttribute('data-post-id');
            const postTitle = this.getAttribute('data-post-title');

            // Обновляем заголовок модального окна
            const modalTitle = document.getElementById('confirmDeleteModalLabel');
            modalTitle.textContent = `Удалить пост: ${postTitle}`;

            // Показываем модальное окно
            const modal = new bootstrap.Modal(confirmDeleteModal);
            modal.show();
        });
    });

    // Обработка подтверждения удаления
    confirmDeleteBtn.addEventListener('click', async function () {
        if (!currentPostId) return;

        const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        try {
            const response = await fetch(`/${adminRoute}/posts/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    post_id: currentPostId,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();

            const modal = bootstrap.Modal.getInstance(confirmDeleteModal);
            modal.hide();

            if (result.success) {
                // Успешно удалён — перезагружаем страницу
                location.reload();
            } else {
                alert('Ошибка: ' + (result.message || 'Не удалось удалить пост.'));
            }
        } catch (error) {
            console.error('Ошибка при удалении поста:', error);
            alert('Произошла ошибка при удалении поста.');
        }
    });
});