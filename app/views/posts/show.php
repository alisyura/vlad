<!-- Блок post_preview -->
<article class="post_full" itemscope itemtype="https://schema.org/Article" data-url="<?= htmlspecialchars($full_url) ?>" data-id="<?= htmlspecialchars($post['url']) ?>">
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

    <?php if ($is_post): ?>
    <!-- Блок post_date_category -->
    <div class="post_preview_date_category" itemprop="datePublished category">
        <time class="post_preview_date" datetime="<?= date('Y-m-d', strtotime($post['updated_at'])) ?>">
            <?= date('d.m.Y', strtotime($post['updated_at'])) ?>
        </time>        
        <span class="spacer"></span> <!-- Промежуток 25px -->
        <img src="/assets/pic/menu/anekdoty.png" alt="<?= htmlspecialchars($post['category_name']) ?>" class="icon">
        <span class="spacer_small"></span> <!-- Промежуток 10px -->
        <a href="/cat/<?= htmlspecialchars($post['category_url']) ?>" class="text_link"><?= htmlspecialchars($post['category_name']) ?></a>
    </div>
    <?php endif ?>

    <!-- Заголовок поста -->
    <h1 class="post_preview_header" itemprop="headline"><?= htmlspecialchars($post['title']) ?></h1>

    <!-- Текст поста -->
    <p class="post_full_text" itemprop="articleBody">
        <?= nl2br($post['content']) ?>
    </p>

    <?php if ($is_post && isset($post_image)): ?>
    <img class="post_preview_oblozhka" alt="Обложка поста" src="<?= $post_image ?>" itemprop="image">
    <?php endif ?>

    <?php  if (isset($post['tags'])): ?>
    <!-- Блок с хэштегами -->
    <div class="tags-container" itemprop="keywords">
        <?php foreach ($post['tags'] as $tag): ?>
        <a href="<?= htmlspecialchars($tags_baseUrl).htmlspecialchars($tag['url']) ?>" class="tag">#<?= htmlspecialchars($tag['name']) ?></a>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <?php if ($is_post): ?>
    <!-- Информационный блок -->
    <div class="admin-block">
        <div class="admin-block-icon-container">
            <!-- Иконка 20x20px -->
            <img src="/assets/pic/admin-block-icon.png" alt="Инфо" width="20" height="20">
        </div>
        <div class="admin-block-text-container">
            <div class="admin-block-title">Комментарий от администрации</div>
            <div class="admin-block-description">Приятно, граждане, наблюдать, как элементы политического процесса формируют глобальную экономическую сеть и при этом — заблокированы в рамках своих собственных рациональных ограничений.</div>
        </div>
    </div>
    
    <!-- Блок реакций -->
    <div class="post_reactions">
        <!-- Лайк -->
        <a href="#" class="reaction like" data-type="like">
            <img src="/assets/pic/ponravilos.png" alt="Нравится" class="icon reaction-icon">
        </a>
        <span class="spacer_small"></span> <!-- Промежуток 15px -->
        <span class="reaction_count like_count">433331</span>
        <span class="spacer_large"></span> <!-- Промежуток 35px -->

        <!-- Дизлайк -->
        <a href="#" class="reaction dislike" data-type="dislike">
            <img src="/assets/pic/ne_ponravilos.png" alt="Не нравится" class="icon reaction-icon">
        </a>
        <span class="spacer_small"></span> <!-- Промежуток 15px -->
        <span class="reaction_count dislike_count">99964</span>
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
    <?php endif ?>
</article>