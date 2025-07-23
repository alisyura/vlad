document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.show-more-btn');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
           // alert('sds');
            const category = this.dataset.category;
           // alert(category);
            const container = document.querySelector(`.section-part[data-category="${category}"]`);
           // alert(container);
            const hiddenItems = container.querySelectorAll('.post-item.hidden');
          //  alert(hiddenItems);

            // Показываем скрытые посты
            hiddenItems.forEach(item => {
                item.style.display = 'block';
            });

            // Убираем кнопку
            this.remove();
        });
    });
});