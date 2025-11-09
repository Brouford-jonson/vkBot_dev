<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Bot.php';
require_once 'models/Command.php';

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
$user_bots = $bot->getBotsByUserId($_SESSION['user_id']);

// Получаем количество команд для каждого бота
$bot_commands_count = [];
$bot_roles_count = [];
foreach ($user_bots as $user_bot) {
    $commands_count = $bot->getCommandsCount($user_bot['id']);
    $bot_commands_count[$user_bot['id']] = $commands_count;
    
    // Получаем количество ролей
    $roles_count = $bot->getRolesCount($user_bot['id']);
    $bot_roles_count[$user_bot['id']] = $roles_count;
}

// Активация/деактивация бота
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bot_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Проверяем, принадлежит ли бот пользователю
    $bot_data = $bot->getBotById($bot_id);
    if ($bot_data && $bot_data['user_id'] == $_SESSION['user_id']) {
        if ($action == 'activate') {
            $bot->id = $bot_id;
            $bot->is_active = true;
            $bot->update();
            $_SESSION['success_message'] = "Бот активирован!";
        } elseif ($action == 'deactivate') {
            $bot->id = $bot_id;
            $bot->is_active = false;
            $bot->update();
            $_SESSION['success_message'] = "Бот деактивирован!";
        } elseif ($action == 'delete') {
            $bot->id = $bot_id;
            $bot->user_id = $_SESSION['user_id'];
            if ($bot->delete()) {
                $_SESSION['success_message'] = "Бот удален!";
            } else {
                $_SESSION['error_message'] = "Ошибка при удалении бота!";
            }
        }
        header("Location: bots.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои боты - VK Bot Manager</title>
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
                        <li class="breadcrumb-item active">Мои боты</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Мои боты</h2>
                    <a href="add_bot.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить бота
                    </a>
                </div>
            </div>
        </div>

        <?php if (count($user_bots) > 0): ?>
            <div class="row">
                <?php foreach ($user_bots as $user_bot): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100 <?php echo $user_bot['is_active'] ? 'border-success' : 'border-secondary'; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-robot me-2"></i><?php echo htmlspecialchars($user_bot['bot_name']); ?>
                                </h5>
                                <span class="badge <?php echo $user_bot['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $user_bot['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Токен:</small>
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($user_bot['bot_token']); ?>" 
                                               readonly id="token-<?php echo $user_bot['id']; ?>">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="toggleTokenVisibility(<?php echo $user_bot['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h5 mb-1"><?php echo $bot_commands_count[$user_bot['id']]; ?></div>
                                            <small class="text-muted">Команд</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h5 mb-1"><?php echo $bot_roles_count[$user_bot['id']]; ?></div>
                                            <small class="text-muted">Ролей</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h5 mb-1">
                                                <?php if ($user_bot['conversation_id']): ?>
                                                    <i class="fas fa-check text-success"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times text-danger"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Привязан</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($user_bot['conversation_id']): ?>
                                    <div class="mt-3">
                                        <small class="text-muted">ID беседы:</small>
                                        <div><?php echo $user_bot['conversation_id']; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <a href="edit_bot.php?id=<?php echo $user_bot['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       data-bs-toggle="tooltip" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="commands.php?bot_id=<?php echo $user_bot['id']; ?>" 
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip" title="Команды">
                                        <i class="fas fa-code"></i>
                                    </a>
                                    <a href="roles.php?bot_id=<?php echo $user_bot['id']; ?>" 
                                       class="btn btn-sm btn-outline-info"
                                       data-bs-toggle="tooltip" title="Роли">
                                        <i class="fas fa-users-cog"></i>
                                    </a>
                                    <?php if ($user_bot['is_active']): ?>
                                        <a href="bots.php?action=deactivate&id=<?php echo $user_bot['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning"
                                           data-bs-toggle="tooltip" title="Деактивировать">
                                            <i class="fas fa-pause"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="bots.php?action=activate&id=<?php echo $user_bot['id']; ?>" 
                                           class="btn btn-sm btn-outline-success"
                                           data-bs-toggle="tooltip" title="Активировать">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="bots.php?action=delete&id=<?php echo $user_bot['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Вы уверены, что хотите удалить бота \"<?php echo htmlspecialchars($user_bot['bot_name']); ?>\"?')"
                                       data-bs-toggle="tooltip" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-robot fa-5x text-muted mb-4"></i>
                <h3>У вас пока нет ботов</h3>
                <p class="text-muted mb-4">Добавьте своего первого бота для управления беседами ВК</p>
                <a href="add_bot.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Добавить первого бота
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Показать/скрыть токен
        function toggleTokenVisibility(botId) {
            const input = document.getElementById('token-' + botId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Инициализация tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>