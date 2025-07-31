
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
});
