<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-6">
        <h2 class="mt-4">Редактирование тэга: <?= htmlspecialchars($tag_to_edit['name']) ?></h2>
        <form id="edit-tag-form">
            <input type="hidden" name="tag_id" id="tag_id" value="<?= htmlspecialchars($tag_to_edit['id']) ?>">
            <div class="form-group">
                <label for="name">Название</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($tag_to_edit['name']) ?>">
            </div>
            <div class="form-group mt-3">
                <label for="url">УРЛ</label>
                <input type="text" class="form-control" id="url" name="url" disabled required value="<?= htmlspecialchars($tag_to_edit['url']) ?>">
            </div>
            <div class="form-group mt-3">
                <label for="caption">Заголовок страницы (caption):</label>
                <input type="text" class="form-control" id="caption" name="caption" value="<?= htmlspecialchars($tag_to_edit['seo_settings']['caption'] ?? '') ?>">
            </div>
            <div class="form-group mt-3">
                <label for="caption_desc">Подзаголовок страницы (caption_desc):</label>
                <input type="text" class="form-control" id="caption_desc" name="caption_desc" value="<?= htmlspecialchars($tag_to_edit['seo_settings']['caption_desc']  ?? '') ?>">
            </div>
            <div class="form-group mt-3">
                <label for="title">Заголовок страницы для поисковика (title):</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($tag_to_edit['seo_settings']['title'] ?? '') ?>">
            </div>
            <div class="form-group mt-3">
                <label for="description">Описание страницы для поисковика (description):</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= htmlspecialchars($tag_to_edit['seo_settings']['description'] ?? '') ?>">
            </div>
            <div class="form-group mt-3">
                <label for="keywords">Ключевые слова для поисковика (keywords):</label>
                <input type="text" class="form-control" id="keywords" name="keywords" value="<?= htmlspecialchars($tag_to_edit['seo_settings']['keywords'] ?? '') ?>">
            </div>
            <div class="form-group mt-3">
                    <label for="robots">Индексация страницы для поисковика (robots):</label>
                    <select class="form-control" id="robots" name="robots" required>
                        <?php foreach ($robotsList as $robotsValue): 
                            $selected = ($tag_to_edit['robots'] === $robotsValue) ? 'selected' : '' ?>
                            <option <?= $selected ?> value="<?= htmlspecialchars($robotsValue) ?>">
                                <?= htmlspecialchars($robotsValue) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <div class="form-group mt-4">
                <button type="button" class="btn btn-primary">Обновить</button>
                <a href="/<?= $adminRoute ?>/tags" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
