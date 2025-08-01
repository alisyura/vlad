<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Список постов</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/create" class="btn btn-sm btn-outline-secondary">
            Добавить новый
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover posts-table">
        <thead>
            <tr>
                <th scope="col" style="width: 1%;">
                    <input type="checkbox" id="select-all-desktop">
                </th>
                <th scope="col">
                    Заголовок
                    <?php 
                    $sortTitleUrlAsc = $data['base_page_url'] . '?sort=title&order=asc';
                    $sortTitleUrlDesc = $data['base_page_url'] . '?sort=title&order=desc';
                    ?>
                    <a href="<?= htmlspecialchars($sortTitleUrlAsc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'title' && $data['current_sort_order'] === 'asc') ? 'active' : '' ?>">▲</a>
                    <a href="<?= htmlspecialchars($sortTitleUrlDesc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'title' && $data['current_sort_order'] === 'desc') ? 'active' : '' ?>">▼</a>
                </th>
                <th scope="col">
                    Автор
                    <?php 
                    $sortAuthorUrlAsc = $data['base_page_url'] . '?sort=author_name&order=asc';
                    $sortAuthorUrlDesc = $data['base_page_url'] . '?sort=author_name&order=desc';
                    ?>
                    <a href="<?= htmlspecialchars($sortAuthorUrlAsc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'author_name' && $data['current_sort_order'] === 'asc') ? 'active' : '' ?>">▲</a>
                    <a href="<?= htmlspecialchars($sortAuthorUrlDesc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'author_name' && $data['current_sort_order'] === 'desc') ? 'active' : '' ?>">▼</a>
                </th>
                <th scope="col">Рубрики</th>
                <th scope="col">Метки</th>
                <th scope="col" class="status-col">
                    Статус
                    <?php 
                    $sortStatusUrlAsc = $data['base_page_url'] . '?sort=status&order=asc';
                    $sortStatusUrlDesc = $data['base_page_url'] . '?sort=status&order=desc';
                    ?>
                    <a href="<?= htmlspecialchars($sortStatusUrlAsc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'status' && $data['current_sort_order'] === 'asc') ? 'active' : '' ?>">▲</a>
                    <a href="<?= htmlspecialchars($sortStatusUrlDesc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'status' && $data['current_sort_order'] === 'desc') ? 'active' : '' ?>">▼</a>
                </th>
                <th scope="col">
                    Дата
                    <?php 
                    $sortDateUrlAsc = $data['base_page_url'] . '?sort=updated_at&order=asc';
                    $sortDateUrlDesc = $data['base_page_url'] . '?sort=updated_at&order=desc';
                    ?>
                    <a href="<?= htmlspecialchars($sortDateUrlAsc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'updated_at' && $data['current_sort_order'] === 'asc') ? 'active' : '' ?>">▲</a>
                    <a href="<?= htmlspecialchars($sortDateUrlDesc) ?>" 
                       class="sort-link <?= ($data['current_sort_by'] === 'updated_at' && $data['current_sort_order'] === 'desc') ? 'active' : '' ?>">▼</a>
                </th>
                <th scope="col" class="actions-header">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data['posts'])): ?>
                <?php foreach ($data['posts'] as $post): ?>
                    <tr class="post-row" data-post-id="<?= htmlspecialchars($post['id']) ?>">
                        <td> 
                            <input type="checkbox" name="post_ids[]" value="<?= htmlspecialchars($post['id']) ?>">
                        </td>
                        <td class="post-title-cell">
                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                            <div class="mobile-details-toggle d-md-none">
                                <i class="bi bi-chevron-down toggle-icon"></i> 
                            </div>
                            
                            <div class="post-mobile-details d-md-none d-none">
                                <div class="detail-item">
                                    <strong>Автор:</strong> <?= htmlspecialchars($post['author_name'] ?? 'Неизвестен') ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Рубрики:</strong> <?= $post['category_names'] ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Метки:</strong> <?= $post['tag_names'] ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Статус:</strong> <?= $post['display_status'] ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Дата:</strong> 
                                    <div>Изменено: <?= htmlspecialchars($post['formatted_updated_at']) ?></div>
                                    <div>Создано: <?= htmlspecialchars($post['formatted_created_at']) ?></div>
                                </div>
                                <div class="post-actions mt-2">
                                    <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/edit/<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-primary mb-1 me-1">Редактировать</a>
                                    <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/delete/<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-danger mb-1 me-1" onclick="return confirm('Вы уверены?');">Удалить</a>
                                    <?php if (!empty($post['url'])): ?>
                                        <a href="/<?= htmlspecialchars($post['url']) ?>.html" target="_blank" class="btn btn-sm btn-outline-info text-secondary mb-1">Посмотреть на сайте</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="post-actions mt-1 d-none d-md-block">
                                <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/edit/<?= htmlspecialchars($post['id']) ?>" class="text-primary me-2">Редактировать</a>
                                <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/delete/<?= htmlspecialchars($post['id']) ?>" class="text-danger" onclick="return confirm('Вы уверены, что хотите удалить этот пост?');">Удалить</a>
                                <?php if (!empty($post['url'])): ?>
                                    <a href="/<?= htmlspecialchars($post['url']) ?>.html" target="_blank" class="text-info">Посмотреть на сайте</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($post['author_name'] ?? 'Неизвестен') ?></td>
                        <td class="d-none d-md-table-cell"><?= $post['category_names'] ?></td>
                        <td class="d-none d-md-table-cell"><?= $post['tag_names'] ?></td>
                        <td class="d-none d-md-table-cell"><?= $post['display_status'] ?></td>
                        <td class="d-none d-md-table-cell">
                            <div>Изменено: <?= htmlspecialchars($post['formatted_updated_at']) ?></div>
                            <div>Создано: <?= htmlspecialchars($post['formatted_created_at']) ?></div>
                        </td>
                        <td class="post-actions-cell d-none d-md-table-cell">
                            <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/edit/<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-primary me-2">Редактировать</a>
                            <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/delete/<?= htmlspecialchars($post['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены, что хотите удалить этот пост?');">Удалить</a>
                            <?php if (!empty($post['url'])): ?>
                                <a href="/<?= htmlspecialchars($post['url']) ?>.html" target="_blank" class="btn btn-sm btn-outline-info">Посмотреть</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-5 no-posts-found">
                        <p class="mb-1">Посты не найдены</p>
                        <a href="/<?= htmlspecialchars($data['adminRoute'] ?? 'admin') ?>/posts/create" class="btn btn-sm btn-outline-primary">Создать первый пост</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Блок пагинации -->
<?php 
$pagination = $data['pagination'] ?? [];
$pagination_links = $data['pagination_links'] ?? [];
$base_page_url = $data['base_page_url'] ?? '';
?>

<?php if (!empty($pagination_links)) : ?>
<nav aria-label="Posts pagination" class="mt-4">
    <ul class="pagination justify-content-center">
        <!-- Кнопка "Предыдущая" -->
        <li class="page-item <?= ($pagination['current_page'] <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/' . ($pagination['current_page'] - 1)) ?>">&laquo;</a>
        </li>
    
        <!-- Ссылки на страницы -->
        <?php foreach ($pagination_links as $num => $link): ?>
            <?php if ($num === '...left' || $num === '...right'): ?>
                <li class="page-item disabled">
                    <span class="page-link">…</span>
                </li>
            <?php else: ?>
                <li class="page-item<?= $num == $pagination['current_page'] ? ' active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($link) ?>">
                        <?= $num ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    
        <!-- Кнопка "Следующая" -->
        <li class="page-item <?= ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($base_page_url . '/' . ($pagination['current_page'] + 1)) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

