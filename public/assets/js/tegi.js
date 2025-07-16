document.addEventListener('DOMContentLoaded', () => {
    const tegiSearchInput = document.getElementById('tegi-search-input');
    const clearButton = document.querySelector('.clear-icon');

    // Очищаем поле при клике на крестик
    clearButton.addEventListener('click', () => {
        tegiSearchInput.value = '';
    });
});