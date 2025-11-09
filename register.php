<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    
    // Валидация
    $errors = [];
    
    if (empty($user->username)) {
        $errors[] = "Имя пользователя обязательно";
    }
    
    if (empty($user->email)) {
        $errors[] = "Email обязателен";
    } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email";
    }
    
    if (empty($user->password)) {
        $errors[] = "Пароль обязателен";
    } elseif (strlen($user->password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов";
    }
    
    // Проверка существующего пользователя
    $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':username', $user->username);
    $check_stmt->bindParam(':email', $user->email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $errors[] = "Пользователь с таким именем или email уже существует";
    }
    
    if (empty($errors)) {
        if ($user->create()) {
            $_SESSION['success_message'] = "Регистрация успешна! Теперь вы можете войти.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Ошибка при создании пользователя";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - VK Bot Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center">Регистрация</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Пароль должен быть не менее 6 символов</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="login.php" class="text-decoration-none">Уже есть аккаунт? Войдите</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Проверка совпадения паролей
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
            }
        });
    </script>
</body>
</html>