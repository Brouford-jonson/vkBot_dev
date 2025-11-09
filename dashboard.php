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
$user_stmt = $user->getUserById();

$bot = new Bot($db);
$user_bots = $bot->getBotsByUserId($_SESSION['user_id']);

// Получаем количество команд для каждого бота
$bot_commands_count = [];
foreach ($user_bots as $user_bot) {
    $count = $bot->getCommandsCount($user_bot['id']);
    $bot_commands_count[$user_bot['id']] = $count;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - VK Bot Manager</title>
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Дашборд
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bots.php">
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
                            <li><span class="dropdown-item-text">Баланс: <?php echo $user_stmt['balance']; ?> руб.</span></li>
                            <li><span class="dropdown-item-text">Тариф: <?php echo $user_stmt['tariff'] == 'premium' ? 'Премиум' : 'Бесплатный'; ?></span></li>
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
        <!-- Статистика -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo count($user_bots); ?></h4>
                                <p class="card-text">Активных ботов</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-robot fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $user_stmt['balance']; ?>₽</h4>
                                <p class="card-text">Баланс</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-wallet fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo array_sum($bot_commands_count); ?></h4>
                                <p class="card-text">Всего команд</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-code fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $user_stmt['tariff'] == 'premium' ? 'Премиум' : 'Бесплатный'; ?></h4>
                                <p class="card-text">Тариф</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-crown fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Быстрые действия</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="add_bot.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Добавить бота
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="payments.php" class="btn btn-success w-100">
                                    <i class="fas fa-credit-card me-2"></i>Пополнить баланс
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="tariffs.php" class="btn btn-warning w-100">
                                    <i class="fas fa-crown me-2"></i>Сменить тариф
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="docs.php" class="btn btn-info w-100">
                                    <i class="fas fa-book me-2"></i>Документация
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Список ботов -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Мои боты</h5>
                        <a href="bots.php" class="btn btn-sm btn-outline-primary">Все боты</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($user_bots) > 0): ?>
                            <div class="row">
                                <?php foreach ($user_bots as $user_bot): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 <?php echo $user_bot['is_active'] ? 'border-success' : 'border-secondary'; ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user_bot['bot_name']); ?></h6>
                                                <span class="badge <?php echo $user_bot['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $user_bot['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        ID беседы: <?php echo $user_bot['conversation_id'] ?: 'Не привязан'; ?>
                                                    </small>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        Команд: <?php echo $bot_commands_count[$user_bot['id']]; ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="card-footer">
                                                <div class="btn-group w-100">
                                                    <a href="edit_bot.php?id=<?php echo $user_bot['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="commands.php?bot_id=<?php echo $user_bot['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-code"></i>
                                                    </a>
                                                    <a href="bot_stats.php?id=<?php echo $user_bot['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                                <h5>У вас пока нет ботов</h5>
                                <p class="text-muted">Добавьте своего первого бота для управления беседами ВК</p>
                                <a href="add_bot.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Добавить бота
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>