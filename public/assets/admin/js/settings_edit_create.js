/**
 * Класс для управления взаимоисключающим выбором между двумя выпадающими списками.
 */
class ExclusiveSelector {
    /**
     * @param {string} categoryId ID элемента select для категории.
     * @param {string} tagId ID элемента select для тега.
     */
    constructor(categoryId, tagId, key) {
        this.categorySelect = document.getElementById(categoryId);
        this.tagSelect = document.getElementById(tagId);
        this.key = document.getElementById(key);

        if (!this.categorySelect || !this.tagSelect || !this.key) {
            console.error('Один или все элементы select или ключ не найдены.');
            return;
        }

        this.init();
    }

    /**
     * Инициализирует слушатели событий для выпадающих списков.
     */
    init() {
        this.categorySelect.addEventListener('change', () => {
            if (this.categorySelect.value !== '') {
                this.tagSelect.value = ''; // Сбросить Тег
                this.key.value = 'cat_' + this.categorySelect.value + '_';
            }
        });

        this.tagSelect.addEventListener('change', () => {
            if (this.tagSelect.value !== '') {
                this.categorySelect.value = ''; // Сбросить Категорию
                this.key.value = 'tag_' + this.tagSelect.value + '_';
            }
        });
    }
}

/**
 * Класс для управления иконкой, связанной с компонентом Bootstrap Collapse.
 */
class CollapseIconToggler {
    /**
     * @param {string} collapseId ID элемента collapse.
     * @param {string} iconSelector CSS-селектор для иконки.
     */
    constructor(collapseId, iconSelector) {
        this.collapseElement = document.getElementById(collapseId);
        this.toggleIcon = document.querySelector(iconSelector);

        if (!this.collapseElement || !this.toggleIcon) {
            // console.warn('Элемент collapse или иконка не найдены.');
            return; // Можно логировать, но не всегда критично
        }

        this.init();
    }

    /**
     * Инициализирует слушатели событий для переключения иконки.
     */
    init() {
        this.collapseElement.addEventListener('shown.bs.collapse', () => {
            this.toggleIcon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        });

        this.collapseElement.addEventListener('hidden.bs.collapse', () => {
            this.toggleIcon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        });
    }
}

// Использование классов после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Создаем экземпляр для управления выбором Категории/Тега
    const selector = new ExclusiveSelector('category_id', 'tag_id', 'key');

    // Создаем экземпляр для управления иконкой подсказки
    const iconToggler = new CollapseIconToggler('collapseKeyHint', '.hint-toggle-icon');
});