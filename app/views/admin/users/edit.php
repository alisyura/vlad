<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-6">
        <h2 class="mt-4">Редактирование пользователя: <?= htmlspecialchars($user_to_edit['name']) ?></h2>
        <form id="edit-user-form">
            <input type="hidden" name="user_id" id="user_id" value="<?= htmlspecialchars($user_to_edit['id']) ?>">
            <div class="form-group">
                <label for="name">Имя</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($user_to_edit['name']) ?>">
            </div>
            <div class="form-group mt-3">
                <label for="login">Логин</label>
                <input type="text" class="form-control" id="login" name="login" disabled required value="<?= htmlspecialchars($user_to_edit['login']) ?>">
            </div>
            <div class="form-group mt-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($user_to_edit['email']) ?>">
            </div>
            <div class="form-group mt-3">
                <label for="password">Новый пароль</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Оставьте пустым, если не хотите менять">
            </div>
            <div class="form-group mt-3">
                <label for="confirm_password">Подтвердите пароль</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            <div class="form-group mt-3">
                <label for="role">Роль</label>
                <select class="form-control" id="role" name="role_id" <?= ($user_to_edit['built_in']) ? 'disabled' : '' ?>>>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role['id']) ?>"
                            <?= ($role['id'] == $user_to_edit['role_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['description']) ?> (<?= htmlspecialchars($role['name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mt-4">
                <button type="button" class="btn btn-primary">Обновить</button>
                <a href="/<?= $adminRoute ?>/users" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
