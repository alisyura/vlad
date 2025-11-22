<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Создание настройки</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="/<?= htmlspecialchars($adminRoute) ?>/settings" class="btn btn-sm btn-outline-secondary">
            << К списку
        </a>
    </div>
</div>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4 text-center"><?= htmlspecialchars($title) ?></h1>

            <!-- ОБЛАСТЬ ДЛЯ ВЫВОДА ОШИБОК -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm p-3 mb-4" role="alert">
                    <h5 class="alert-heading fw-bold d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Обнаружены ошибки при выполнении:
                    </h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- БЛОК С ПОДСКАЗКОЙ О ПРАВИЛАХ КЛЮЧА -->
            <div class="alert alert-secondary border-0 rounded-3 shadow-sm p-3 mb-4" role="alert">
                <!-- Заголовок-переключатель. Добавлены атрибуты data-bs-* и cursor: pointer -->
                <h6 class="alert-heading mb-0 fw-bold d-flex align-items-center text-primary" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#collapseKeyHint" 
                    aria-expanded="false" 
                    aria-controls="collapseKeyHint"
                    style="cursor: pointer;">
                    
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Правила именования Ключа Настройки
                    <!-- Иконка-индикатор. Используем auto-margin ms-auto для выравнивания вправо -->
                    <i class="bi bi-chevron-down ms-auto hint-toggle-icon"></i>
                </h6>
                
                <div class="collapse" id="collapseKeyHint">
                    <hr class="mt-2 mb-2">
                    <p class="mb-1 fs-6">
                        Для корректного использования ключа в системе, он должен быть в формате **snake_case** (все буквы маленькие, слова разделены нижним подчеркиванием).<br>
                        Название ключа только на английском. При выборе категории или тэга его урл проставляется и заменяет значение ключа.<br>
                        Категорию и тэг выбирать, чтобы создать настройки для страниц со списком постов отображаемых при выборе рубрики или тэга.<br>
                        <br>Основные ключи для категорий и тэгов:<br>
                        <ul class="list-unstyled mb-0 fs-6 ms-3">
                            <li>&lt;Урл категории или тэга&gt;<b>_caption</b> - заголовок страницы</li>
                            <li>&lt;Урл категории или тэга&gt;<b>_caption_desc</b> - подзаголовок страницы</li>
                            <li>&lt;Урл категории или тэга&gt;<b>_description</b> - описание страницы для поисковика</li>
                            <li>&lt;Урл категории или тэга&gt;<b>_keywords</b> - ключевые слова для поисковика</li>
                        </ul>
                    </p>
                    
                    <ul class="list-unstyled mb-0 fs-6 ms-3">
                        <li><strong class="text-success">✅ Правильно:</strong> <code>home_page_title</code>, <code>analytics_script_id</code></li>
                        <li><strong class="text-danger">❌ Неправильно:</strong> <code>HomePageTitle</code>, <code>analytics-script-id</code></li>
                    </ul>
                </div>
            </div>
            <!-- КОНЕЦ БЛОКА С ПОДСКАЗКОЙ -->

            <div class="card p-4">
                <form method="POST" action="/<?= htmlspecialchars($adminRoute) ?>/settings/create">
                    
                    <!-- ГРУППА И КЛЮЧ -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="group_name" class="form-label fw-bold">Имя Группы</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="group_name" 
                                   name="group_name" 
                                   placeholder="Например: General, Homepage, Analytics"
                                   list="existingGroupsList"
                                   value="<?= htmlspecialchars($curGroup) ?>">
                            <datalist id="existingGroupsList">
                                <?php 
                                // Предполагается, что $existingGroups — это массив имен групп
                                foreach ($existingGroupsList ?? [] as $group): ?>
                                    <option value="<?= htmlspecialchars($group) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text">Оставьте пустым для 'NoGroup'.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="key" class="form-label fw-bold">Ключ Настройки <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="key" 
                                   name="key" 
                                   required 
                                   placeholder="Например: page_title_template"
                                   oninput="handleKeyInput(this)"
                                   value="<?= htmlspecialchars($curKey) ?>">
                            <div class="form-text">Уникальный ключ для этой настройки.</div>
                        </div>
                    </div>

                    <!-- ЗНАЧЕНИЕ -->
                    <div class="mb-3">
                        <label for="value" class="form-label fw-bold">Значение <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="value" 
                                  name="value" 
                                  rows="4" 
                                  required
                                  placeholder="Введите значение настройки (текст, число, JSON и т.д.)"><?= htmlspecialchars($curValue) ?></textarea>
                    </div>

                    <!-- ПРИВЯЗКА К СУЩНОСТИ -->
                    <h5 class="mt-4 mb-3 text-secondary">Привязка (Выберите только 1 или оставьте пустым для Глобальной)</h5>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-bold">Категория</label>
                            <select class="form-select" id="category" name="category_id">
                                <option value="" selected>-- Глобальная (Не привязывать) --</option>
                                <?php foreach ($categoriesList as $category): 
                                    $selected = $category['url'] === $curCategoryUrl ? 'selected' : '';
                                    ?>
                                    <option <?= $selected ?> value="<?= $category['url'] ?>">
                                        <?= htmlspecialchars($category['name']) ?> (<?= htmlspecialchars($category['url']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="tag_id" class="form-label fw-bold">Тег</label>
                            <select class="form-select" id="tag" name="tag_id">
                                <option value="" selected>-- Глобальная (Не привязывать) --</option>
                                <?php foreach ($tagsList as $tag): 
                                    $selected = $tag['url'] === $curTagUrl ? 'selected' : '';
                                    ?>
                                    <option <?= $selected ?> value="<?= $tag['url'] ?>">
                                        <?= htmlspecialchars($tag['name']) ?> (<?= htmlspecialchars($tag['url']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- КОММЕНТАРИЙ -->
                    <div class="mb-3">
                        <label for="comment" class="form-label fw-bold">Комментарий</label>
                        <textarea class="form-control" 
                                  id="comment" 
                                  name="comment" 
                                  rows="2" 
                                  placeholder="Описание назначения этой настройки"><?= htmlspecialchars($curComment) ?></textarea>
                    </div>

                   

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Создать Настройку</button>
                    </div>
                </form>
            </div>
            

        </div>
    </div>
</div>