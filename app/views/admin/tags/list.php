<?php 
// Предполагаемые переменные, переданные из контроллера:
// $tags: Массив объектов пользователей или ассоциативных массивов из базы данных.
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-md-8">
            <h2>Список тэгов</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Кол-во постов</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tags)): ?>
                        <tr>
                            <td colspan="3">Тэги не найдены.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><?= htmlspecialchars($tag['name']) ?></td>
                                <td><?= htmlspecialchars($tag['post_count']) ?></td>
                                <td>
                                    [ <a href="/<?= $adminRoute ?>/tags/edit/<?= htmlspecialchars($tag['id']) ?>">Редактировать</a> ]
                                    [ <a href="#" class="action-link" data-action="delete" data-id="<?= htmlspecialchars($tag['id']) ?>">Удалить</a> ]
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
                            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] - 1)) . $sort_string ?>">&laquo;</a>
                        </li>
                    
                        <!-- Ссылки на страницы -->
                        <?php foreach ($pagination_links as $num => $link): ?>
                            <?php if ($num === '...left' || $num === '...right'): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">…</span>
                                </li>
                            <?php else: ?>
                                <li class="page-item<?= $num == $pagination['current_page'] ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($link) . $sort_string?>">
                                        <?= $num ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    
                        <!-- Кнопка "Следующая" -->
                        <li class="page-item <?= ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/p' . ($pagination['current_page'] + 1)) . $sort_string ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <h2>Создать новый тэг</h2>
            <form id="create-tag-form">
                <div class="form-group">
                    <label for="name">Название:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="login">УРЛ (изменить будет нельзя):</label>
                    <input type="text" class="form-control" id="url" name="url" required>
                </div>
                <button type="button" class="btn btn-primary mt-10px">Создать тэг</button>
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