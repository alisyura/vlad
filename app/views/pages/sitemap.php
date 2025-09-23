<!-- Блок post_preview -->
<article class="sitemap_full" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($full_url) ?>" data-id="<?= htmlspecialchars($full_url) ?>">
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
    <h1 class="sitemap_header" itemprop="headline">Карта сайта</h1>

    <!-- Текст поста -->
    <p class="sitemap_content" itemprop="articleBody">
        <div class="section-part">
            <div class="category-link">
                <span class="spacer"></span> <!-- Промежуток 25px -->
                <img src="/assets/pic/menu/glavnaya.png" alt="Главная" class="icon" />
                <span class="spacer_small"></span> <!-- Промежуток 10px -->
                <a href="/" class="sitemap-link">Главная</a>
            </div>
        </div>

        <?php if(!empty($data['post'])): ?>
            <?php foreach($data['post'] as $category): ?>
            <div class="section-part" data-category="<?= htmlspecialchars($category['url']) ?>">
                <div class="category-link">
                    <span class="spacer"></span> <!-- Промежуток 25px -->
                    <img src="/assets/pic/menu/<?= $category['url'] ?>.png" alt="<?= $category['name'] ?>" class="icon" />
                    <span class="spacer_small"></span> <!-- Промежуток 10px -->
                    <a href="/cat/<?= $category['url'] ?>" class="sitemap-link"><?= $category['name'] ?></a>
                </div>

                <div class="posts-block">
                    <?php
                        $posts = $category['posts'];
                        $firstFive = array_slice($posts, 0, 5);
                        $rest = array_slice($posts, 5);
                    ?>

                    <!-- Первые 5 постов -->
                    <?php foreach ($firstFive as $post): ?>
                        <div class="post-item">
                            <a href="/<?= htmlspecialchars($post['url']) ?>.html" class="sitemap-link">&ndash; <?= htmlspecialchars($post['title']) ?></a>
                        </div>
                    <?php endforeach ?>

                    <!-- Остальные посты (скрыты по умолчанию) -->
                    <?php if (!empty($rest)): ?>
                        <?php foreach ($rest as $post): ?>
                            <div class="post-item hidden">
                                <a href="/<?= htmlspecialchars($post['url']) ?>.html" class="sitemap-link">&ndash; <?= htmlspecialchars($post['title']) ?></a>
                            </div>
                        <?php endforeach ?>

                        <!-- Кнопка "Показать ещё" -->
                        <button type="button" class="show-more-btn" data-category="<?= htmlspecialchars($category['url']) ?>">Показать ещё</button>
                    <?php endif ?>
                </div>
            </div>
            <?php endforeach ?>
        <?php endif ?>

        <?php if(!empty($data['page']['pages'])): ?>
            <div class="posts-block">
            <?php foreach ($data['page']['pages'] as $page): ?>
                <div class="post-item">
                    <a href="/page/<?= htmlspecialchars($page['url']) ?>.html" class="sitemap-link"><?= htmlspecialchars($page['title']) ?></a>
                </div>
            <?php endforeach ?>
            </div>
        <?php endif ?>
    </p>
</article>