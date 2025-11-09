<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Bot.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user_data = $user->getUserById();

$bot = new Bot($db);

// Проверяем лимит ботов по тарифу
if (!$user->canCreateMoreBots()) {
    $max_bots = $user_data['tariff'] == 'premium' ? 20 : 5;
    $_SESSION['error_message'] = "Вы достигли лимита ботов ($max_bots) для вашего тарифа. Удалите неиспользуемых ботов или улучшите тариф.";
    header("Location: bots.php");
    exit;
}

// Обработка добавления бота
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bot_name = trim($_POST['bot_name']);
    $bot_token = trim($_POST['bot_token']);
    $group_id = intval($_POST['group_id']);
    $conversation_id = intval($_POST['conversation_id']);
    $validation_type = $_POST['validation_type'] ?? 'simple';
    
    $errors = [];
    
    if (empty($bot_name)) {
        $errors[] = "Введите название бота";
    }
    
    if (empty($bot_token)) {
        $errors[] = "Введите токен бота";
    } else {
        // Выбираем тип валидации
        if ($validation_type === 'strict') {
            if (!$bot->validateToken($bot_token)) {
                $errors[] = "Неверный формат токена. Токен должен быть в формате vk1.a.xxx или состоять из букв и цифр.";
            }
        } else {
            // Простая валидация
            if (!$bot->validateTokenSimple($bot_token)) {
                $errors[] = "Токен слишком короткий или содержит недопустимые символы";
            }
        }
        
        // Проверка через API (опционально)
        if (empty($errors) && isset($_POST['check_api']) && $_POST['check_api'] === '1') {
            if (!$bot->testToken($bot_token)) {
                $errors[] = "Токен не прошел проверку через API ВК. Убедитесь, что токен действителен и имеет необходимые права. Вы можете продолжить без проверки API.";
            }
        }
    }
    
    if (empty($errors)) {
        $bot->user_id = $_SESSION['user_id'];
        $bot->bot_name = $bot_name;
        $bot->bot_token = $bot_token;
        $bot->group_id = $group_id;
        $bot->conversation_id = $conversation_id;
        $bot->is_active = true;
        
        if ($bot->create()) {
            $_SESSION['success_message'] = "Бот успешно добавлен!";
            header("Location: bots.php");
            exit;
        } else {
            $errors[] = "Ошибка при добавлении бота";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить бота - VK Bot Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fab fa-vk me-2"></i>VK Bot Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Дашборд
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bots.php">
                            <i class="fas fa-robot me-1"></i>Мои боты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-wallet me-1"></i>Пополнение счета
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><span class="dropdown-item-text">Баланс: <?php echo $user_data['balance']; ?> руб.</span></li>
                            <li><span class="dropdown-item-text">Тариф: <?php echo $user_data['tariff'] == 'premium' ? 'Премиум' : 'Бесплатный'; ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-cog me-1"></i>Настройки</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Выйти</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Дашборд</a></li>
                        <li class="breadcrumb-item"><a href="bots.php">Мои боты</a></li>
                        <li class="breadcrumb-item active">Добавить бота</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Добавить нового бота</h5>
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

                        <form method="POST">
                            <div class="mb-3">
                                <label for="bot_name" class="form-label">Название бота *</label>
                                <input type="text" class="form-control" id="bot_name" name="bot_name" 
                                       value="<?php echo isset($_POST['bot_name']) ? htmlspecialchars($_POST['bot_name']) : ''; ?>" 
                                       required>
                                <div class="form-text">Произвольное название для идентификации</div>
                            </div>

                            <div class="mb-3">
                                <label for="bot_token" class="form-label">Токен бота ВК *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="bot_token" name="bot_token" 
                                           value="<?php echo isset($_POST['bot_token']) ? htmlspecialchars($_POST['bot_token']) : ''; ?>" 
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" id="show_token">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <strong>Поддерживаемые форматы токенов:</strong>
                                    <ul>
                                        <li>Новые токены: <code>vk1.a.xxxxxxxx...</code> (рекомендуется)</li>
                                        <li>Старые токены: 85 символов (буквы и цифры)</li>
                                    </ul>
                                    <a href="instructions.php" target="_blank">
                                        Как получить токен?
                                    </a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Настройки валидации</label>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="validation_type" 
                                           id="validation_simple" value="simple" checked>
                                    <label class="form-check-label" for="validation_simple">
                                        Простая проверка (рекомендуется)
                                    </label>
                                    <div class="form-text">Проверяет только длину и безопасность токена</div>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="validation_type" 
                                           id="validation_strict" value="strict">
                                    <label class="form-check-label" for="validation_strict">
                                        Строгая проверка формата
                                    </label>
                                    <div class="form-text">Проверяет точный формат токена ВК</div>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_api" name="check_api" value="1">
                                    <label class="form-check-label" for="check_api">
                                        Проверить токен через API ВК
                                    </label>
                                    <div class="form-text">
                                        Дополнительная проверка работоспособности токена. Может занять несколько секунд.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="group_id" class="form-label">ID сообщества ВК</label>
                                <input type="number" class="form-control" id="group_id" name="group_id" 
                                       value="<?php echo isset($_POST['group_id']) ? htmlspecialchars($_POST['group_id']) : ''; ?>">
                                <div class="form-text">ID сообщества, от имени которого работает бот</div>
                            </div>

                            <div class="mb-3">
                                <label for="conversation_id" class="form-label">ID беседы</label>
                                <input type="number" class="form-control" id="conversation_id" name="conversation_id" 
                                       value="<?php echo isset($_POST['conversation_id']) ? htmlspecialchars($_POST['conversation_id']) : ''; ?>">
                                <div class="form-text">ID беседы, к которой привязан бот (можно добавить позже)</div>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Информация о новых токенах ВК:</h6>
                                <p>ВК перешел на новые токены формата <code>vk1.a.xxxxxxxx</code>. Эти токены:</p>
                                <ul>
                                    <li>Более безопасные</li>
                                    <li>Имеют переменную длину</li>
                                    <li>Содержат информацию о времени жизни и правах</li>
                                    <li>Полностью поддерживаются нашей системой</li>
                                </ul>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="bots.php" class="btn btn-secondary me-md-2">Отмена</a>
                                <button type="submit" class="btn btn-primary">Добавить бота</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Показать/скрыть токен
        document.getElementById('show_token').addEventListener('click', function() {
            const tokenInput = document.getElementById('bot_token');
            const icon = this.querySelector('i');
            
            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                tokenInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        // Автоматическое определение типа валидации
        document.getElementById('bot_token').addEventListener('input', function() {
            const token = this.value;
            
            // Если токен начинается с vk1.a., выбираем простую валидацию
            if (token.startsWith('vk1.a.')) {
                document.getElementById('validation_simple').checked = true;
            }
        });
    </script>
</body>
</html>