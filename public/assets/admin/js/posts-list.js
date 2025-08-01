document.addEventListener('DOMContentLoaded', function () {
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


    // Логика раскрытия/скрытия деталей на мобильных
    const mobileToggleButtons = document.querySelectorAll('.mobile-details-toggle');

    mobileToggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            console.log('Клик по кнопке переключения:', this); 
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
                        toggleIcon.classList.remove('bi-chevron-up');
                        toggleIcon.classList.add('bi-chevron-down');
                    } else {
                        // Блок виден, показываем стрелку вверх
                        toggleIcon.classList.remove('bi-chevron-down');
                        toggleIcon.classList.add('bi-chevron-up');
                    }
                } else {
                    console.error('Блок .post-mobile-details НЕ НАЙДЕН в строке:', row);
                }
            } else {
                console.error('Родительская строка .post-row НЕ НАЙДЕНА для кнопки:', this);
            }
        });
    });
});