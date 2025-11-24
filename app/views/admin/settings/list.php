<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Настройки</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($allowEdit): ?>
            <a href="<?= htmlspecialchars($basePageUrl) ?>/create" class="btn btn-sm btn-outline-secondary">
                Добавить
            </a>
        <?php else: ?>
            &nbsp;
        <?php endif ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4 p-3 border rounded bg-light">
    <div class="flex-grow-1 ms-4">
        <form class="row g-2 align-items-center justify-content-end" action='<?= htmlspecialchars($filter['formAction'] ?? '') ?>' method="GET">
            
            <div class="col-12 col-md-auto">
                <select class="form-select form-select-sm" name="category">
                    <option value="">— Все рубрики —</option>
                    <?php if (!empty($filter) && !empty($filter['categories'] ?? [])): ?>
                        <?php foreach ($filter['categories'] as $category): ?>
                            <?php $selected = ($category['url'] == $filter['selectedCategory'] ? 'selected' : '') ?>
                            <option <?= htmlspecialchars($selected) ?> value="<?= htmlspecialchars($category['url']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach ?>
                    <?php endif ?>
                </select>
            </div>

            <div class="col-12 col-md-auto">
                <select class="form-select form-select-sm" name="tag">
                    <option value="">— Все тэги —</option>
                    <?php if (!empty($filter) && !empty($filter['tags'] ?? [])): ?>
                        <?php foreach ($filter['tags'] as $tag): ?>
                            <?php $selected = ($tag['url'] === $filter['selectedTag'] ? 'selected' : '') ?>
                            <option <?= htmlspecialchars($selected) ?> value="<?= htmlspecialchars($tag['url']) ?>"><?= htmlspecialchars($tag['name']) ?></option>
                        <?php endforeach ?>
                    <?php endif ?>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <input value="<?= htmlspecialchars($filter['selectedSearchQuery'] ?? '') ?>" type="text" id="searchquery" name="searchquery" class="form-control form-control-sm" placeholder="Поиск по названию и значению">
            </div>

            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i> Искать
                </button>
            </div>
        </form>
    </div>
</div>



<div class="container mt-5">
    <?php 
        if (empty($groupedSettingsList)): 
    ?>
        <div class="alert alert-info">Настройки не найдены.</div>
    <?php 
        else: 
            $tabIndex = 0; // Индекс для уникальных ID
    ?>


    <!-- НАВИГАЦИЯ (Вкладки) -->
    <ul class="nav nav-tabs mb-3" id="seoTabs" role="tablist">
        <?php foreach ($groupedSettingsList as $groupName => $settingsList): ?>
            <?php 
                $tabId = 'tab-' . $groupName;
                $tabIdClean = preg_replace('/[^a-zA-Z0-9_-]/', '', $tabId); // Очистка для HTML ID
                $isActive = ($tabIndex === 0) ? 'active' : ''; 
            ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $isActive ?>" 
                        id="<?= $tabIdClean ?>-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#<?= $tabIdClean ?>" 
                        type="button" 
                        role="tab" 
                        aria-controls="<?= $tabIdClean ?>" 
                        aria-selected="<?= ($tabIndex === 0) ? 'true' : 'false' ?>">
                    <?= htmlspecialchars($groupName) ?>
                </button>
            </li>
        <?php $tabIndex++; endforeach; ?>
    </ul>


    <!-- СОДЕРЖИМОЕ ВКЛАДОК -->
    <div class="tab-content" id="seoTabsContent">
        <?php 
        $tabIndex = 0; // Сброс индекса
        foreach ($groupedSettingsList as $groupName => $settingsList): 
        ?>
            <?php 
                $tabId = 'tab-' . $groupName;
                $tabIdClean = preg_replace('/[^a-zA-Z0-9_-]/', '', $tabId);
                $isActive = ($tabIndex === 0) ? 'active show' : ''; 
            ?>
            <div class="tab-pane fade <?= $isActive ?> p-3 border bg-white rounded-3" 
                 id="<?= $tabIdClean ?>" 
                 role="tabpanel" 
                 aria-labelledby="<?= $tabIdClean ?>-tab">
                
                <h3 class="mb-4 text-primary">Настройки группы <?= htmlspecialchars($groupName) ?></h3>

                <?php if ($groupName === "Cache"): ?>
                    <button type="submit" class="btn btn-secondary mb-2">Очистить кэш</button>
                <?php endif ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover posts-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    Название
                                </th>
                                <th scope="col">
                                    Значение
                                </th>
                                <th scope="col">Категория</th>
                                <th scope="col">Тэг</th>
                                <th scope="col" class="status-col">
                                    Комментарий
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($settingsList as $setting): 
                                $settingId = $setting['id'];
                                $settingKey = htmlspecialchars($setting['key']);
                                $settingValue = htmlspecialchars($setting['value'] ?? '');
                                $settingComment = htmlspecialchars($setting['comment'] ?? '');
                                $settingBuiltin = htmlspecialchars($setting['builtin'] ?? '');
                                $settingCategoryName = htmlspecialchars($setting['category_name'] ?? '');
                                $settingCategoryUrl = htmlspecialchars($setting['category_url'] ?? '');
                                $settingTagName = htmlspecialchars($setting['tag_name'] ?? '');
                                $settingTagUrl = htmlspecialchars($setting['tag_url'] ?? '');
                            ?>
                            <tr class="post-row" data-post-id="<?= $settingId ?>">
                                <td class="post-title-cell">
                                    <strong><?= $settingKey ?></strong>
                                    <div class="mobile-details-toggle d-md-none">
                                        <i class="bi bi-chevron-down toggle-icon"></i> 
                                    </div>
                                    
                                    <div class="post-mobile-details d-md-none d-none">
                                        <div class="detail-item">
                                            <strong>Значение:</strong> <?= $settingValue ?>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Категория:</strong> <?= $settingCategoryName ?>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Тэг:</strong> <?= $settingTagName ?>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Комментарий:</strong> <?= $settingComment ?>
                                        </div>
                                        <div class="post-actions mt-2">
                                            <!-- Для мобильных -->
                                             <?php if ($allowEdit): ?>
                                            <a href="<?= htmlspecialchars($basePageUrl) ?>/edit/<?= $settingId ?>" class="btn btn-sm btn-outline-primary mb-1 me-1">Редактировать</a>
                                                <?php if ($settingBuiltin === '0'): ?>
                                                    <a href="#" 
                                                        class="btn btn-sm btn-outline-danger mb-1 me-1 delete-post-link" 
                                                        data-post-id="<?= $settingId ?>"
                                                        data-action="delete"
                                                        data-post-title="<?= $settingKey ?>">
                                                        Удалить
                                                    </a>
                                                <?php endif ?>
                                            <?php endif ?>
                                        </div>
                                    </div>

                                    <div class="post-actions mt-1 d-none d-md-block">
                                        <!-- Для десктопа -->
                                        <?php if ($allowEdit): ?>
                                        <a href="<?= htmlspecialchars($basePageUrl) ?>/edit/<?= $settingId ?>" class="text-primary me-2">Редактировать</a>
                                            <?php if ($settingBuiltin === '0'): ?>
                                                <a href="#" 
                                                    class="btn btn-sm text-danger mb-1 me-1 delete-post-link" 
                                                    data-post-id="<?= $settingId ?>"
                                                    data-action="delete"
                                                    data-post-title="<?= $settingKey ?>">
                                                    Удалить
                                                </a>
                                            <?php endif ?>
                                        <?php endif ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><?= $settingValue ?></td>
                                <td class="d-none d-md-table-cell"><?= $settingCategoryName ?></td>
                                <td class="d-none d-md-table-cell"><?= $settingTagName ?></td>
                                <td class="d-none d-md-table-cell"><?= $settingComment ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php $tabIndex++; endforeach; ?>
    </div>

    <?php endif; // Конец проверки empty($groupedSettings) ?>
</div>
<br>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Подтвердите удаление</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить этот пост? Это действие нельзя отменить.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Нет, отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Да, удалить</button>
            </div>
        </div>
    </div>
</div>

