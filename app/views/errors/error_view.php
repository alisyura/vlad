<!-- app/views/errors/error_view.php -->

<div class='error_view'>
    <div>
        <h3 class="error_view_header" style="font-size: 19px;"><?= htmlspecialchars($title) ?></h4>
        <p class="error_view_text"><?= htmlspecialchars($error_message) ?></p>
    </div>

    <div class="error_view_text">
        <h4>Что можно сделать?</h5>
        <ul>
            <li>Попробуйте обновить страницу.</li>
            <li><a href="/">Вернуться на главную страницу</a></li>
        </ul>
    </div>
</div>