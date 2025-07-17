document.addEventListener('DOMContentLoaded', async function () {
    const searchInput = document.getElementById('tegi-search-input');
    const searchResult = document.querySelector('.tegi-search-result');

    // Функция загрузки тегов
    async function loadTags(query = '') {
        try {
            // alert('query = '+query);
            const response = await fetch('/api/search_tags', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name: query })
            });

            const data = await response.json();
            console.error('Ответ сервера:\n'+JSON.stringify(data, null, 2));

            if (!data.success) {
                const errorMessage = data.message || 'Ошибка загрузки тегов';
                throw new Error(errorMessage);
            }
            // Очищаем текущий список
            searchResult.innerHTML = '';

            // Проверяем, что data.tagslist существует и это массив
            if (!data || !Array.isArray(data.tagslist) || data.tagslist.length === 0) {
                const noResult = document.createElement('div');
                noResult.textContent = 'Ничего не найдено';
                noResult.style.color = '#706f69';
                noResult.style.padding = '8px';
                searchResult.appendChild(noResult);
                return;
            }

            // Выводим теги без id
            data.tagslist.forEach(tag => {
                const tagLink = document.createElement('a');
                tagLink.href = `/tag/${tag.url}`;
                tagLink.className = 'tag';
                tagLink.textContent = `#${tag.name}`;
                // tagLink.addEventListener('click', function (e) {
                //     e.preventDefault();
                //     searchInput.value = tag.name;
                //     loadTags(tag.name);
                // });
                searchResult.appendChild(tagLink);
            });

        } catch (error) {
            console.error('Ошибка при загрузке тегов:', error);
            searchResult.innerHTML = '<div style="color: red">Ошибка загрузки тегов</div>';
        }
    }

    // Загружаем все теги при открытии страницы
    loadTags();

    // Дебонсинг для поля ввода
    let debounceTimer;
    searchInput.addEventListener('input', function () {
        const query = this.value.trim();

        clearTimeout(debounceTimer);

        if (query.length >= 3 || query === '') {
            debounceTimer = setTimeout(() => {
                loadTags(query);
            }, 300); // 300 мс задержка
        }
    });

    // Очистка поля поиска
    document.querySelector('.clear-icon')?.addEventListener('click', function () {
        searchInput.value = '';
        loadTags();
    });
});