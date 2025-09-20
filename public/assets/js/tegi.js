class TagSearch {
    constructor(searchInputId, resultSelector) {
        this.searchInput = document.getElementById(searchInputId);
        this.searchResult = document.querySelector(resultSelector);
        this.debounceTimer = null;

        if (!this.searchInput || !this.searchResult) {
            console.error('Не найдены необходимые DOM-элементы.');
            return;
        }

        this.initEventListeners();
        this.loadTags();
    }

    initEventListeners() {
        this.searchInput.addEventListener('input', this.handleInput.bind(this));

        const clearIcon = document.querySelector('.clear-icon');
        if (clearIcon) {
            clearIcon.addEventListener('click', this.handleClear.bind(this));
        }
    }

    handleInput() {
        const query = this.searchInput.value.trim();
        clearTimeout(this.debounceTimer);

        if (query.length >= 3 || query === '') {
            this.debounceTimer = setTimeout(() => {
                this.loadTags(query);
            }, 300);
        }
    }

    handleClear() {
        this.searchInput.value = '';
        this.loadTags();
    }

    async loadTags(query = '') {
        try {
            const params = new URLSearchParams({ name: query });
            const url = `/api/search_tags?${params.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                const errorMessage = data.message || 'Ошибка загрузки тегов';
                throw new Error(errorMessage);
            }

            this.renderTags(data.tagslist);

        } catch (error) {
            console.error('Ошибка при загрузке тегов:', error);
            this.searchResult.innerHTML = '<div style="color: red">Ошибка загрузки тегов</div>';
        }
    }

    renderTags(tagslist) {
        this.searchResult.innerHTML = '';

        if (!Array.isArray(tagslist) || tagslist.length === 0) {
            const noResult = document.createElement('div');
            noResult.textContent = 'Ничего не найдено';
            noResult.style.color = '#706f69';
            noResult.style.padding = '8px';
            this.searchResult.appendChild(noResult);
            return;
        }

        tagslist.forEach(tag => {
            const tagLink = document.createElement('a');
            tagLink.href = `/tag/${tag.url}`;
            tagLink.className = 'tag';
            tagLink.textContent = `#${tag.name}`;
            this.searchResult.appendChild(tagLink);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TagSearch('tegi-search-input', '.tegi-search-result');
});