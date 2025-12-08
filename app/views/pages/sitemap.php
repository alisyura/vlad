<!-- Блок post_preview -->
<div class="sitemap_full" itemscope itemtype="https://schema.org/WebPage">
    <!-- Schema.org внутри блока -->
    <meta itemscope itemprop="mainEntityOfPage" itemType="https://schema.org/WebPage" itemid="<?= htmlspecialchars($full_url) ?>"/>
    
    <!-- Заголовок поста -->
    <h1 class="sitemap_header">Карта сайта</h1>

    <!-- Текст поста -->
    <div class="sitemap_content">
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

        <div class="section-part">
            <div class="category-link">
                <span class="spacer"></span> <!-- Промежуток 25px -->
                <img src="/assets/pic/page.png" alt="Страницы" class="icon" />
                <span class="spacer_small"></span> <!-- Промежуток 10px -->
                <a href="/" class="sitemap-link">Страницы</a>
            </div>

            <?php if(!empty($data['page']['pages'])): ?>
                <div class="posts-block">
                <?php foreach ($data['page']['pages'] as $page): ?>
                    <div class="post-item">
                        <a href="/page/<?= htmlspecialchars($page['url']) ?>.html" class="sitemap-link"><?= htmlspecialchars($page['title']) ?></a>
                    </div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>