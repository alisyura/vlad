<?php foreach ($posts as $post): ?>
    <!-- Блок post_preview -->
    <div class="post_preview">
        <!-- Блок post_date_category -->
        <div class="post_preview_date_category">
            <span class="post_preview_date"><?= stringDate($post['created_at']) ?></span>
            <span class="spacer"></span> <!-- Промежуток 25px -->
            <img src="/pic/menu/<?= $post['category_url'] ?>.png" alt="<?= $post['category_name'] ?>" class="icon">
            <span class="spacer_small"></span> <!-- Промежуток 10px -->
            <a href="#" class="text_link"><?= $post['category_name'] ?></a>
        </div>

        <!-- Заголовок поста -->
        <h3 class="post_preview_header"><a href="/post/<?= htmlspecialchars($post['url']) ?>"><?= htmlspecialchars($post['title']) ?></a></h3>

        <!-- Текстовый превью поста -->
        <p class="post_text_preview">
            <?= htmlspecialchars(create_excerpt($post['content'])) ?>
            <a href="/post/<?= $post['url'] ?>" class="text_link">Читать далее></a>
        </p>

        <!-- Блок реакций -->
        <div class="post_reactions">
            <a href="#"><img src="/pic/ponravilos.png" alt="Нравится" class="icon"></a>
            <span class="spacer_small"></span> <!-- Промежуток 15px -->
            <span class="reaction_count like_count"><?= $post['likes'] ?></span>
            <span class="spacer_large"></span> <!-- Промежуток 35px -->
            <a href="#"><img src="/pic/ne_ponravilos.png" alt="Комментарии" class="icon"></a>
            <span class="spacer_small"></span> <!-- Промежуток 15px -->
            <span class="reaction_count dislike_count"><?= $post['dislikes'] ?></span>
            <span class="spacer_large"></span> <!-- Промежуток 35px -->
            <a href="#"><img src="/pic/pereslat.png" alt="Поделиться" class="icon"></a>
        </div>
    </div>
<?php endforeach; ?>