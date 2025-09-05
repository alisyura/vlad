<?php 
// Предполагаемые переменные, переданные из контроллера:
// $users: Массив объектов пользователей или ассоциативных массивов из базы данных.
// $roles: Массив объектов ролей или ассоциативных массивов из базы данных.
// isUserAdmin: Является ли пользователь админом
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-md-8">
            <h2>Список тэгов</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Статус</th>
                        <?php if ($isUserAdmin): ?>
                            <th>Действия</th>
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="<?= $isUserAdmin ? 3 : 2 ?>">Тэги не найдены.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td>
                                    <?php if ($user['active']): ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Заблокирован</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isUserAdmin): ?>
                                    <td>
                                        [ <a href="/<?= $adminRoute ?>/users/edit/<?= htmlspecialchars($user['id']) ?>">Редактировать</a> ]
                                        <?php if ($user['built_in'] === 0): ?>
                                            <?php if ($user['active']): ?>
                                                [ <a href="#" class="action-link" data-action="block" data-id="<?= htmlspecialchars($user['id']) ?>">Заблокировать</a> ]
                                            <?php else: ?>
                                                [ <a href="#" class="action-link" data-action="unblock" data-id="<?= htmlspecialchars($user['id']) ?>">Разблокировать</a> ]
                                            <?php endif; ?>
                                            
                                            [ <a href="#" class="action-link" data-action="delete" data-id="<?= htmlspecialchars($user['id']) ?>">Удалить</a> ]
                                        <?php endif ?>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($isUserAdmin): ?>
        <div class="col-md-4">
            <h2>Создать нового пользователя</h2>
            <form id="create-user-form">
                <div class="form-group">
                    <label for="name">Имя:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text" class="form-control" id="login" name="login" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label for="role">Роль:</label>
                    <select class="form-control" id="role" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>">
                                <?= htmlspecialchars($role['description']) ?> (<?= htmlspecialchars($role['name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary mt-10px">Создать пользователя</button>
            </form>
        </div>
        <?php endif ?>
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