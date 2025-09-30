<!-- Блок post_preview -->
<div class="tegi_full">
    <!-- Заголовок поста -->
    <h1 class="tegi_header">Теги</h1>

    <p class="tegi_content">
        <div class="tegi-search-container">
            <form class='form-tegi-search-input' action="/cat/tegi-results.html" 
                itemprop="potentialAction" itemscope="" 
                itemtype="http://schema.org/SearchAction">
                <input type="text" id="tegi-search-input" class="tegi-search-input" 
                    placeholder="Поиск тэгов..." name="q" value='<?= htmlspecialchars($search_tag) ?>'>
                <input itemprop="query-input" type="hidden" name="query">
                <span class="search-icon icon" aria-hidden="true">
                    <img src="/assets/pic/poisk.png" alt="Лупа" width="20" height="20">
                </span>
            </form>
        </div>

        <div class="tegi-search-result">
            <?php if (empty($tags)): ?>
                <div style='color: #706f69; padding: 8px;'>Ничего не найдено</div>
            <?php else: ?>
                <?php foreach ($tags as $tag): ?>
                    <a class='tag' href='/tag/<?= htmlspecialchars($tag['url']) ?>'><?= htmlspecialchars($tag['name']) ?></a>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </p>

</div>
