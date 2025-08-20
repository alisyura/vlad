document.addEventListener('DOMContentLoaded', function() {
    // Получаем данные из скрытых полей
    const tagsInput = document.getElementById('tagsInput');
    const tagsList = document.getElementById('tagsList');
    const tagSuggestions = document.getElementById('tagSuggestions');

    const initialTagsDataEl = document.getElementById('initialTagsData');
    const selectedTagsDataEl = document.getElementById('selectedTagsData');

    const existingTags = JSON.parse(initialTagsDataEl.value);
    const selectedTags = JSON.parse(selectedTagsDataEl.value);
    
    //1. Подбор тэгов
        function createTagElement(tagUrl, tagName) {
            const tagSpan = document.createElement('span');
            tagSpan.className = 'badge bg-secondary d-flex align-items-center me-2 mb-2';
            tagSpan.innerHTML = `
                ${tagName}
                <input type="hidden" name="tags[]" value="${tagName}">
                <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove tag"></button>
            `;

            const closeButton = tagSpan.querySelector('.btn-close');
            closeButton.addEventListener('click', () => {
                tagSpan.remove();
            });

            return tagSpan;
        }
        
        function addTag(tagUrl, tagName) {
            const existingInput = tagsList.querySelector(`input[value="${tagUrl}"]`);
            if (!existingInput) {
                tagsList.appendChild(createTagElement(tagUrl, tagName));
            }
        }
    
    if (selectedTags.length > 0) {
        selectedTags.forEach(tagUrl => {
            const existingTag = existingTags.find(t => t.url === tagUrl);
            const tagName = existingTag ? existingTag.name : tagUrl.replace(/-/g, ' ');
            addTag(tagUrl, tagName);
        });
    }

    tagsInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const tagValue = this.value.trim();
            
            if (tagValue) {
                // Разделяем строку на отдельные теги по запятой
                const tags = tagValue.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                
                tags.forEach(tagName => {
                    const tagUrl = tagName.toLowerCase()
                                        .replace(/[^a-zа-яё0-9- ]/g, '')
                                        .replace(/ /g, '-');
                    addTag(tagUrl, tagName);
                });
            }
            
            this.value = '';
            tagSuggestions.innerHTML = '';
        }
    });
    
    let debounceTimeout;
    tagsInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        const query = this.value.trim();

        if (query.length < 3) {
            tagSuggestions.innerHTML = '';
            return;
        }

        debounceTimeout = setTimeout(async () => {
            try {
                const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content;
                if (!csrfToken) {
                    alert('Ошибка: CSRF-токен не найден.');
                    return;
                }

                const url = `/${adminRoute}/tags/search`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ q: query })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const tags = await response.json();
                
                tagSuggestions.innerHTML = '';
                if (tags.length > 0) {
                    tags.forEach(tag => {
                        const suggestionItem = document.createElement('a');
                        suggestionItem.href = '#';
                        suggestionItem.className = 'list-group-item list-group-item-action';
                        suggestionItem.textContent = tag.name;
                        suggestionItem.addEventListener('click', (e) => {
                            e.preventDefault();
                            addTag(tag.url, tag.name);
                            tagsInput.value = '';
                            tagSuggestions.innerHTML = '';
                        });
                        tagSuggestions.appendChild(suggestionItem);
                    });
                }
            } catch (error) {
                console.error('Ошибка при поиске меток:', error);
            }
        }, 300);
    });
    
    document.addEventListener('click', (e) => {
        if (!tagsInput.contains(e.target) && !tagSuggestions.contains(e.target)) {
            tagSuggestions.innerHTML = '';
        }
    });    
});