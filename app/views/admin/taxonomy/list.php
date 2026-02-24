<?php 
// Предполагаемые переменные, переданные из контроллера:
// $tags: Массив объектов пользователей или ассоциативных массивов из базы данных.
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-md-8">
            <h2><?= htmlspecialchars($taxonomyListTitle) ?></h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Кол-во постов</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($taxonomies)): ?>
                        <tr>
                            <td colspan="3"><?= htmlspecialchars($taxonomyNotFoundMsg) ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($taxonomies as $taxonomy): ?>
                            <tr>
                                <td><?= htmlspecialchars($taxonomy['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($taxonomy['post_count'] ?? '') ?></td>
                                <td>
                                    [ <a href="/<?= $adminRoute ?>/taxonomy/<?= htmlspecialchars($taxonomyType ?? '') ?>/edit/<?= htmlspecialchars($taxonomy['id'] ?? '') ?>">Редактировать</a> ]
                                    <?php if (($taxonomy['builtin'] ?? 0) === 0): ?>
                                        [ <a href="#" class="action-link" data-action="delete" data-id="<?= htmlspecialchars($taxonomy['id'] ?? '') ?>">Удалить</a> ]
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Блок пагинации -->
            <?php if (!empty($pagination_links)) : ?>
                <nav aria-label="Posts pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Кнопка "Предыдущая" -->
                        <li class="page-item <?= ($pagination['current_page'] <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] - 1)) ?>">&laquo;</a>
                        </li>
                    
                        <!-- Ссылки на страницы -->
                        <?php foreach ($pagination_links as $num => $link): ?>
                            <?php if ($num === '...left' || $num === '...right'): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">…</span>
                                </li>
                            <?php else: ?>
                                <li class="page-item<?= $num == $pagination['current_page'] ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($link)?>">
                                        <?= $num ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    
                        <!-- Кнопка "Следующая" -->
                        <li class="page-item <?= ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] + 1)) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <h2><?= htmlspecialchars($createFormTitle) ?></h2>
            <form id="create-taxonomy-form">
                <div class="form-group">
                    <label for="name">Название:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="url">УРЛ (изменить будет нельзя):</label>
                    <input type="text" class="form-control" id="url" name="url" required>
                </div>
                <div class="form-group">
                    <label for="caption">Заголовок страницы (caption):</label>
                    <input type="text" class="form-control" id="caption" name="caption">
                </div>
                <div class="form-group">
                    <label for="caption_desc">Подзаголовок страницы (caption_desc):</label>
                    <input type="text" class="form-control" id="caption_desc" name="caption_desc">
                </div>
                <div class="form-group">
                    <label for="title">Заголовок страницы для поисковика (title):</label>
                    <input type="text" class="form-control" id="title" name="title">
                </div>
                <div class="form-group">
                    <label for="description">Описание страницы для поисковика (description):</label>
                    <input type="text" class="form-control" id="description" name="description">
                </div>
                <div class="form-group">
                    <label for="keywords">Ключевые слова для поисковика (keywords):</label>
                    <input type="text" class="form-control" id="keywords" name="keywords">
                </div>
                <div class="form-group">
                    <label for="robots">Индексация страницы для поисковика (robots):</label>
                    <select class="form-control" id="robots" name="robots" required>
                        <?php foreach ($robotsList as $robotsValue): ?>
                            <option value="<?= htmlspecialchars($robotsValue) ?>">
                                <?= htmlspecialchars($robotsValue) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary mt-10px"><?= htmlspecialchars($createButtonTitle) ?></button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="actionModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn" id="confirmActionButton"></button>
            </div>
        </div>
    </div>
</div>