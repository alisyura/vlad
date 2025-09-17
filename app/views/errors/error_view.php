<!-- app/views/errors/error_view.php -->

<div class='post_preview'>
    <div>
        <h3 class="post_preview_header" style="font-size: 19px;"><?= htmlspecialchars($title) ?></h4>
        <p class="post_text_preview"><?= htmlspecialchars($error_message) ?></p>
    </div>

    <div class="post_text_preview">
        <h4>Что можно сделать?</h5>
        <ul>
            <li>Попробуйте обновить страницу.</li>
            <li><a href="/">Вернуться на главную страницу</a></li>
        </ul>
    </div>
</div>