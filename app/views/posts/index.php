<?php if (($show_caption) && !empty($caption)): ?>
<!-- Заголовок (при категории) -->
<div style="background-color: #fafaf8; margin-top: 20px; padding: 20px; border-radius: 10px;">
    <div style="font-weight: bold; font-size: 18px;">
        <?= htmlspecialchars($caption) ?>
    </div>
    <?php if (trim($caption_desc ?? '') !== ''): ?>
    <div class="post_text_preview">
        <?= htmlspecialchars($caption_desc) ?>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<?php foreach ($posts as $post): ?>
<!-- Блок post_preview -->
<div class="post_preview" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($url) ?>/<?= htmlspecialchars($post['url']) ?>.html" data-id="<?= htmlspecialchars($post['url']) ?>">
    <!-- Schema.org внутри блока -->
    <meta itemprop="url" content="<?= htmlspecialchars($url) ?>/<?= htmlspecialchars($post['url']) ?>.html"> 
    <meta itemprop="description" content="<?= get_clean_description(create_excerpt($post['content'])) ?>">

    <div itemprop="publisher" itemscope itemtype="https://schema.org/Organization" style="display: none;">
        <meta itemprop="name" content="<?= htmlspecialchars($export['site_name']) ?>">
        <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
            <meta itemprop="url" content="<?= htmlspecialchars($export['image']) ?>"> 
        </div>
        <meta itemprop="url" content="<?= htmlspecialchars($url) ?>"> 
    </div>

    <div itemprop="author" itemscope itemtype="https://schema.org/Organization" style="display: none;">
        <meta itemprop="name" content="<?= htmlspecialchars($export['site_name']) ?>"> 
    </div>

    <div class="post_preview_bookmark">
        <a href="/<?= htmlspecialchars($post['url']) ?>.html"><?= htmlspecialchars($post['id']) ?></a>
    </div>
    
    <!-- Блок post_date_category -->
    <div class="post_preview_date_category">
        <time itemprop="datePublished" datetime="<?= htmlspecialchars($post['updated_at']) ?>" class="post_preview_date"><?= stringDate($post['updated_at']) ?></time>
        <span class="spacer"></span> <!-- Промежуток 25px -->
        <img src="/assets/pic/menu/<?= $post['category_url'] ?>.png" alt="<?= $post['category_name'] ?>" class="icon">
        <span class="spacer_small"></span> <!-- Промежуток 10px -->
        <a href="/cat/<?= $post['category_url'] ?>" class="text_link"><?= $post['category_name'] ?></a>
    </div>

    <!-- Заголовок поста -->
    <h3 itemprop="headline" class="post_preview_header"><?= htmlspecialchars($post['title']) ?></h3>

    <!-- Текстовый превью поста -->
    <p class="post_text_preview">
        <?php if ($show_read_next): ?>
        <div itemprop="description"><?= get_clean_description(create_excerpt($post['content'])) ?></div>
        <a href="/<?= $post['url'] ?>.html" class="text_link">Читать ></a>
        <?php else: ?>
        <div itemprop="articleBody"><?= strip_and_allow_tags($post['content']) ?></div>
        <?php endif ?>
    </p>

    <?php if (isset($post['image'])): ?>
    <img class="post_preview_oblozhka" alt="Обложка поста" src="<?= htmlspecialchars($url).htmlspecialchars($post['image']) ?>" itemprop="image">
    <?php endif ?>

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
<?php if (!empty($pagination_links)) : ?>
<div class="pagination">
    <?php if ($pagination['current_page'] > 1): ?>     
        <a class="page-number" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] - 1)) ?>">&laquo;</a>
    <?php endif; ?>

    <?php foreach ($pagination_links as $num => $link): ?>
        <?php if ($num === '...left' || $num === '...right'): ?>
            <span class="dots"><?= $link ?></span>
        <?php else: ?>
            <a href="<?= htmlspecialchars($link) /*htmlspecialchars($base_page_url.$link)*/ ?>"
            class="page-number<?= $num == $pagination['current_page'] ? ' active' : '' ?>">
               <?= $num ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
        <a class="page-number" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] + 1)) ?>">&raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>
