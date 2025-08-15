<!-- app/views/admin/error_view.php -->
<div class="container-fluid pt-3">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <h4 class="alert-heading mb-1">Произошла ошибка</h4>
                    <p class="mb-0"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Что можно сделать?</h5>
                    <ul>
                        <li>Попробуйте обновить страницу.</li>
                        <li>Убедитесь, что база данных доступна.</li>
                        <li>Проверьте логи сервера на наличие деталей ошибки.</li>
                        <li><a href="/<?= htmlspecialchars($adminRoute) ?>/dashboard">Вернуться на главную админки</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>