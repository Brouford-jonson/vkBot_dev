<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления ботами ВК</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">VK Bot Manager</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <h1>Управление ботами ВКонтакте</h1>
                <p class="lead">Создавайте и настраивайте ботов для бесед ВК</p>
                <ul>
                    <li>🤖 Привязка к беседам</li>
                    <li>⚙️ Команды модерации</li>
                    <li>🔧 Свои команды</li>
                    <li>💳 Гибкая система тарифов</li>
                </ul>
                <a href="login.php" class="btn btn-primary btn-lg">Начать</a>
            </div>
            <div class="col-md-6">
                <img src="assets/images/bot-preview.png" alt="Bot Preview" class="img-fluid">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>