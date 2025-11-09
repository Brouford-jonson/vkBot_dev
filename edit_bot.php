<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Bot.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: bots.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user_data = $user->getUserById();

$bot = new Bot($db);
$bot_id = intval($_GET['id']);
$bot_data = $bot->getBotById($bot_id);

// Проверяем, принадлежит ли бот пользователю
if (!$bot_data || $bot_data['user_id'] != $_SESSION['user_id']) {
    header("Location: bots.php");
    exit;
}

// Обработка обновления бота
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bot_name = trim($_POST['bot_name']);
    $bot_token = trim($_POST['bot_token']);
    $group_id = intval($_POST['group_id']);
    $conversation_id = intval($_POST['conversation_id']);
    $is_active = isset($_POST['is_active']) ? true : false;
    
    $errors = [];
    
    if (empty($bot_name)) {
        $errors[] = "Введите название бота";
    }
    
    if (empty($bot_token)) {
        $errors[] = "Введите токен бота";
    } elseif (!$bot->validateToken($bot_token)) {
        $errors[] = "Неверный формат токена";
    }
    
    if (empty($errors)) {
        $bot->id = $bot_id;
        $bot->user_id = $_SESSION['user_id'];
        $bot->bot_name = $bot_name;
        $bot->bot_token = $bot_token;
        $bot->group_id = $group_id;
        $bot->conversation_id = $conversation_id;
        $bot->is_active = $is_active;
        
        if ($bot->update()) {
            $_SESSION['success_message'] = "Бот успешно обновлен!";
            header("Location: bots.php");
            exit;
        } else {
            $errors[] = "Ошибка при обновлении бота";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать бота - VK Bot Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Навигация (аналогичная bots.php) -->
    <!-- ... -->

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Дашборд</a></li>
                        <li class="breadcrumb-item"><a href="bots.php">Мои боты</a></li>
                        <li class="breadcrumb-item active">Редактировать бота</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Редактировать бота: <?php echo htmlspecialchars($bot_data['bot_name']); ?></h5>
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
                                       value="<?php echo isset($_POST['bot_name']) ? htmlspecialchars($_POST['bot_name']) : htmlspecialchars($bot_data['bot_name']); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="bot_token" class="form-label">Токен бота ВК *</label>
                                <input type="password" class="form-control" id="bot_token" name="bot_token" 
                                       value="<?php echo isset($_POST['bot_token']) ? htmlspecialchars($_POST['bot_token']) : htmlspecialchars($bot_data['bot_token']); ?>" 
                                       required>
                                <div class="form-text">
                                    <a href="https://vk.com/club{{ID_СООБЩЕСТВА}}?act=tokens" target="_blank">
                                        Как получить токен?
                                    </a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="group_id" class="form-label">ID сообщества ВК</label>
                                <input type="number" class="form-control" id="group_id" name="group_id" 
                                       value="<?php echo isset($_POST['group_id']) ? htmlspecialchars($_POST['group_id']) : htmlspecialchars($bot_data['group_id']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="conversation_id" class="form-label">ID беседы</label>
                                <input type="number" class="form-control" id="conversation_id" name="conversation_id" 
                                       value="<?php echo isset($_POST['conversation_id']) ? htmlspecialchars($_POST['conversation_id']) : htmlspecialchars($bot_data['conversation_id']); ?>">
                                <div class="form-text">
                                    Для получения ID беседы: 
                                    <a href="https://vk.com/dev/messages.getConversations" target="_blank">
                                        инструкция
                                    </a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (isset($_POST['is_active']) ? $_POST['is_active'] : $bot_data['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Бот активен</label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="bots.php" class="btn btn-secondary me-md-2">Отмена</a>
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Информация о боте -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Информация о боте</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Создан:</strong> <?php echo date('d.m.Y H:i', strtotime($bot_data['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Статус:</strong> 
                                    <span class="badge <?php echo $bot_data['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $bot_data['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>