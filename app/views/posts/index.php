<?php if ($show_caption): ?>
<!-- Заголовок (при категории) -->
<div style="background-color: #fafaf8; margin-top: 20px; padding: 20px; border-radius: 10px;">
    <div style="font-weight: bold; font-size: 18px;">
        Это выделенный пост
    </div>
    <div class="post_text_preview">
        Это текстовое превью выделенного поста. Здесь может быть краткое описание статьи или рекламное сообщение.
    </div>
</div>
<?php endif ?>

<?php foreach ($posts as $post): ?>
<!-- Блок post_preview -->
<div class="post_preview" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($url) ?>/<?= htmlspecialchars($post['url']) ?>.html" data-id="<?= htmlspecialchars($post['url']) ?>">
    <!-- Schema.org внутри блока -->
    <meta itemprop="headline" content="Эскимос заблудился на охоте">
    <meta itemprop="description" content="Эскимос шёл за тюленем, но попал к чукче. Теперь он не может найти обратную дорогу.">
    <meta itemprop="url" content="https://вашсайт.ru/post/1"> 
    <meta itemprop="image" content="https://вашсайт.ru/pic/oblozhка1.jpg"> 
    <meta itemprop="datePublished" content="2024-10-12T12:00:00+03:00">
    <meta itemprop="author" content="Автор 1">
    <meta itemprop="publisher" content="Ваш сайт">

    <!-- Блок post_date_category -->
    <div class="post_preview_date_category">
        <span class="post_preview_date"><?= stringDate($post['updated_at']) ?></span>
        <span class="spacer"></span> <!-- Промежуток 25px -->
        <img src="/assets/pic/menu/<?= $post['category_url'] ?>.png" alt="<?= $post['category_name'] ?>" class="icon">
        <span class="spacer_small"></span> <!-- Промежуток 10px -->
        <a href="#" class="text_link"><?= $post['category_name'] ?></a>
    </div>

    <!-- Заголовок поста -->
    <h3 class="post_preview_header"><a href="/<?= htmlspecialchars($post['url']) ?>.html"><?= htmlspecialchars($post['title']) ?></a></h3>

    <!-- Текстовый превью поста -->
    <p class="post_text_preview">
        <?php if ($show_read_next): ?>
        <?= htmlspecialchars(create_excerpt($post['content'])) ?>
        <a href="/<?= $post['url'] ?>.html" class="text_link">Читать ></a>
        <?php else: ?>
        <?= htmlspecialchars($post['content']) ?>
        <?php endif ?>
    </p>

    <!-- Блок реакций -->
    <div class="post_reactions">
        <!-- Лайк -->
        <a href="#" class="reaction like" data-type="like">
            <img src="/assets/pic/ponravilos.png" alt="Нравится" class="icon reaction-icon">
        </a>
        <span class="spacer_small"></span> <!-- Промежуток 15px -->
        <span class="reaction_count like_count"><?= $post['likes'] ?></span>
        <span class="spacer_large"></span> <!-- Промежуток 35px -->

        <!-- Дизлайк -->
        <a href="#" class="reaction dislike" data-type="dislike">
            <img src="/assets/pic/ne_ponravilos.png" alt="Не нравится" class="icon reaction-icon">
        </a>
        <span class="spacer_small"></span> <!-- Промежуток 15px -->
        <span class="reaction_count dislike_count"><?= $post['dislikes'] ?></span>
        <span class="spacer_large"></span> <!-- Промежуток 35px -->
        <!-- <a href="#"><img src="pic/pereslat.png" alt="Поделиться" class="icon"></a> -->

        <!-- Кнопка Поделиться -->
        <div class="share-dropdown">
            <a href="#" class="share-trigger">
                <img src="/assets/pic/pereslat.png" alt="Поделиться" class="icon">
            </a>
            
            <!-- Меню поделиться -->
            <div class="share-menu">
                <div class="share-option">
                    <div class="share-icon-container">
                        <img src="/assets/pic/copy-link.png" alt="Копировать ссылку" width="18" height="18">
                    </div>
                    <div class="share-text-container">
                        <a href="#" class="share-link" onclick="copyLink(event)">Скопировать ссылку</a>
                    </div>
                </div>
                <div class="share-option">
                    <div class="share-icon-container">
                        <img src="/assets/pic/share-tg.png" alt="Поделиться в телеграм" width="18" height="18">
                    </div>
                    <div class="share-text-container">
                        <a href="#" class="share-link" onclick="shareTo('tg', event)">Поделиться в телеграм</a>
                    </div>
                </div>
                <div class="share-option">
                    <div class="share-icon-container">
                        <img src="/assets/pic/share-wa.png" alt="Поделиться в WhatsApp" width="18" height="18">
                    </div>
                    <div class="share-text-container">
                        <a href="#" class="share-link" onclick="shareTo('wa', event)">Поделиться в WhatsApp</a>
                    </div>
                </div>
                <div class="share-option">
                    <div class="share-icon-container">
                        <img src="/assets/pic/share-vk.png" alt="Поделиться в VK" width="18" height="18">
                    </div>
                    <div class="share-text-container">
                        <a href="#" class="share-link" onclick="shareTo('vk', event)">Поделиться в VK</a>
                    </div>
                </div>
                <div class="share-option">
                    <div class="share-icon-container">
                        <img src="/assets/pic/share-ok.png" alt="Поделиться в Одноклассниках" width="18" height="22">
                    </div>
                    <div class="share-text-container">
                        <a href="#" class="share-link" onclick="shareTo('ok', event)">Поделиться в Одноклассниках</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Пагинация -->
<div class="pagination">
    <a href="#" class="page-number active">1</a>
    <a href="#" class="page-number">2</a>
    <a href="#" class="page-number">3</a>
</div>