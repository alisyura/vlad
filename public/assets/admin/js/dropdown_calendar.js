class DropDownCalendar {
    constructor(postDateInput) {
        if (postDateInput!=null)
        {
            this.defaultDate = this.parseDateFromDMY(postDateInput);
        }
        this.initCalendar();
    }

    initCalendar() {
        flatpickr("#post_date", {
            // настройки flatpickr
            locale: "ru", 
            dateFormat: "d-m-Y", 
            defaultDate: this.defaultDate,

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

    parseDateFromDMY(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return new Date();
        }
        
        const parts = dateString.split('-');
        
        if (parts.length !== 3) {
            console.warn('Invalid date format:', dateString);
            return new Date();
        }
        
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = parseInt(parts[2], 10);
        
        // Проверяем, что получились валидные числа
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            console.warn('Invalid date components:', dateString);
            return new Date();
        }
        
        const date = new Date(year, month, day);
        
        // Проверяем, что дата валидна (например, 32 января не пройдет)
        if (date.getDate() !== day || date.getMonth() !== month || date.getFullYear() !== year) {
            console.warn('Invalid date:', dateString);
            return new Date();
        }
        
        return date;
    }

}
