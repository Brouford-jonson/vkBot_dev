<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user_data = $user->getUserById();

// Обработка смены тарифа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tariff'])) {
    $tariff = $_POST['tariff'];
    $cost = $tariff == 'premium' ? 500 : 0;
    
    if ($tariff == 'free' || ($tariff == 'premium' && $user->hasSufficientBalance($cost))) {
        if ($tariff == 'premium') {
            $user->deductBalance($cost);
        }
        
        $user->updateTariff($tariff);
        $_SESSION['success_message'] = "Тариф успешно изменен!";
        header("Location: tariffs.php");
        exit;
    } else {
        $error = "Недостаточно средств на счете";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тарифы - VK Bot Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Навигация (аналогичная payments.php) -->
    <!-- ... -->

    <div class="container mt-4">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h1>Выберите подходящий тариф</h1>
                <p class="lead">Расширьте возможности ваших ботов</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <!-- Бесплатный тариф -->
            <div class="col-md-5 mb-4">
                <div class="card tariff-free h-100">
                    <div class="card-header text-center py-4">
                        <h3 class="card-title">Бесплатный</h3>
                        <div class="price h2 mt-3">0 ₽</div>
                        <small class="text-muted">навсегда</small>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>До 5 ботов</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>До 4 ролей на бота</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Базовые команды модерации</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>Свои команды</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>Приоритетная поддержка</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>Расширенная статистика</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <?php if ($user_data['tariff'] == 'free'): ?>
                            <button class="btn btn-outline-primary w-100" disabled>Текущий тариф</button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="tariff" value="free">
                                <button type="submit" class="btn btn-outline-primary w-100">Выбрать</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Премиум тариф -->
            <div class="col-md-5 mb-4">
                <div class="card tariff-premium border-warning h-100">
                    <div class="card-header bg-warning text-center py-4">
                        <h3 class="card-title">Премиум</h3>
                        <div class="price h2 mt-3">500 ₽</div>
                        <small class="text-muted">в месяц</small>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>До 20 ботов</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>До 10 ролей на бота</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Все команды модерации</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Свои команды</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Приоритетная поддержка</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Расширенная статистика</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <?php if ($user_data['tariff'] == 'premium'): ?>
                            <button class="btn btn-warning w-100" disabled>Текущий тариф</button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="tariff" value="premium">
                                <button type="submit" class="btn btn-warning w-100">
                                    <?php if ($user_data['balance'] >= 500): ?>
                                        Активировать
                                    <?php else: ?>
                                        Недостаточно средств
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Сравнение тарифов -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Сравнение тарифов</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Функция</th>
                                        <th class="text-center">Бесплатный</th>
                                        <th class="text-center">Премиум</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Количество ботов</td>
                                        <td class="text-center">5</td>
                                        <td class="text-center">20</td>
                                    </tr>
                                    <tr>
                                        <td>Роли на бота</td>
                                        <td class="text-center">4</td>
                                        <td class="text-center">10</td>
                                    </tr>
                                    <tr>
                                        <td>Свои команды</td>
                                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Приоритетная поддержка</td>
                                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Стоимость</td>
                                        <td class="text-center">Бесплатно</td>
                                        <td class="text-center">500 руб./мес</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>