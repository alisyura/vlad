<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Посты</h5>
                <p class="card-text display-4"><?= $posts_count ?? 0 ?></p>
                <a href="/<?= $adminRoute ?>/posts" class="btn btn-primary w-100">Управление</a>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Страницы</h5>
                <p class="card-text display-4"><?= $pages_count ?? 0 ?></p>
                <a href="/<?= $adminRoute ?>/pages" class="btn btn-primary w-100">Управление</a>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Пользователи</h5>
                <p class="card-text display-4"><?= $users_count ?? 1 ?></p>
                <a href="/<?= $adminRoute ?>/users" class="btn btn-primary w-100">Управление</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Последние действия</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($recent_activities ?? [] as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($activity['action']) ?></strong>
                                <small class="text-muted"><?= $activity['user'] ?></small>&nbsp;
                                <small class="text-muted"><?= $activity['date'] ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>