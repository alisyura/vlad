<!-- Блок post_preview -->
<article class="tegi_full" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($full_url) ?>" data-id="<?= htmlspecialchars($post['url']) ?>">
    <!-- Schema.org внутри блока -->
    <meta itemprop="headline" content="<?= htmlspecialchars($post['title']) ?>">
    <meta itemprop="description" content="Эскимос шёл за тюленем, но попал к чукче. Теперь он не может найти обратную дорогу.">
    <meta itemprop="url" content="<?= htmlspecialchars($full_url) ?>"> 
    <?php if ($is_post && isset($post_image)): ?>
    <meta itemprop="image" content="<?= $post_image ?>"> 
    <?php endif ?>
    <meta itemprop="datePublished" content="2023-10-12T12:00:00+03:00">
    <meta itemprop="author" content="Автор поста">
    <meta itemprop="publisher" content="Ваш сайт">
    <meta itemprop="keywords" content="анекдоты, чукча, охота, юмор">

    <!-- Заголовок поста -->
    <h1 class="tegi_header" itemprop="headline">Теги</h1>

    <p class="tegi_content" itemprop="articleBody">
        <div class="tegi-search-container">
            <input type="text" id="tegi-search-input" class="tegi-search-input" placeholder="Поиск тэгов...">
            <span class="search-icon icon" aria-hidden="true">
                <img src="/assets/pic/poisk.png" alt="Лупа" width="20" height="20">
            </span>
            <button type="button" class="clear-icon icon" aria-label="Очистить поле поиска">
                <img src="/assets/pic/sbros-poiska.png" alt="Очистить" width="20" height="20">
            </button>
        </div>

        <div class="tegi-search-result">
            <a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a><a href="http://vlad.local/tag/anekdot-dnya" class="tag">#Анекдот дня</a>
            <a href="http://vlad.local/tag/smeshnoe" class="tag">#Смешное</a>
        </div>
    </p>

</article>