<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку</title>
    <link rel="stylesheet" href="<?= asset('admin/css/auth.css') ?>">
</head>
<body>
    <div class="auth-container">
        <form method="POST" class="auth-form">
            <!-- CSRF токен -->
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

            <div class="form-group">
                <label for="login" class="form-label">Логин</label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    class="form-input" 
                    placeholder="" 
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Пароль</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-button">Войти</button>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</body>
</html>