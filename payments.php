<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Payment.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user_data = $user->getUserById();

$payment = new Payment($db);
$user_payments = $payment->getPaymentsByUserId($_SESSION['user_id']);

// Обработка пополнения счета
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    $payment_system = $_POST['payment_system'];
    
    if ($amount >= 10) { // Минимальная сумма пополнения
        $payment->user_id = $_SESSION['user_id'];
        $payment->amount = $amount;
        $payment->payment_system = $payment_system;
        
        if ($payment->create()) {
            // Здесь будет интеграция с платежной системой
            // Пока просто обновляем баланс
            $user->updateBalance($amount);
            $payment->updateStatus('completed');
            
            $_SESSION['success_message'] = "Счет успешно пополнен на " . $amount . " руб.!";
            header("Location: payments.php");
            exit;
        } else {
            $error = "Ошибка при создании платежа";
        }
    } else {
        $error = "Минимальная сумма пополнения - 10 руб.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пополнение счета - VK Bot Manager</title>
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
                        <a class="nav-link" href="bots.php">
                            <i class="fas fa-robot me-1"></i>Мои боты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">
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
                        <li class="breadcrumb-item active">Пополнение счета</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Пополнение счета -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Пополнение счета</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Текущий баланс:</strong> <?php echo $user_data['balance']; ?> руб.
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Сумма пополнения (руб.)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       min="10" max="10000" step="10" value="100" required>
                                <div class="form-text">Минимальная сумма: 10 руб.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Способ оплаты</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_system" 
                                               id="yoomoney" value="yoomoney" checked>
                                        <label class="form-check-label" for="yoomoney">
                                            <i class="fab fa-youtube me-1"></i>ЮMoney
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_system" 
                                               id="card" value="card">
                                        <label class="form-check-label" for="card">
                                            <i class="fas fa-credit-card me-1"></i>Банковская карта
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_system" 
                                               id="qiwi" value="qiwi">
                                        <label class="form-check-label" for="qiwi">
                                            <i class="fas fa-wallet me-1"></i>QIWI
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Пополнить счет
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Быстрое пополнение -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Быстрое пополнение</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <form method="POST" class="h-100">
                                    <input type="hidden" name="amount" value="100">
                                    <input type="hidden" name="payment_system" value="card">
                                    <button type="submit" class="btn btn-outline-primary w-100 h-100 py-3">
                                        <div class="h5 mb-1">100 ₽</div>
                                        <small>Базовый</small>
                                    </button>
                                </form>
                            </div>
                            <div class="col-6 mb-3">
                                <form method="POST" class="h-100">
                                    <input type="hidden" name="amount" value="500">
                                    <input type="hidden" name="payment_system" value="card">
                                    <button type="submit" class="btn btn-outline-success w-100 h-100 py-3">
                                        <div class="h5 mb-1">500 ₽</div>
                                        <small>Стандарт</small>
                                    </button>
                                </form>
                            </div>
                            <div class="col-6 mb-3">
                                <form method="POST" class="h-100">
                                    <input type="hidden" name="amount" value="1000">
                                    <input type="hidden" name="payment_system" value="card">
                                    <button type="submit" class="btn btn-outline-warning w-100 h-100 py-3">
                                        <div class="h5 mb-1">1000 ₽</div>
                                        <small>Профи</small>
                                    </button>
                                </form>
                            </div>
                            <div class="col-6 mb-3">
                                <form method="POST" class="h-100">
                                    <input type="hidden" name="amount" value="5000">
                                    <input type="hidden" name="payment_system" value="card">
                                    <button type="submit" class="btn btn-outline-danger w-100 h-100 py-3">
                                        <div class="h5 mb-1">5000 ₽</div>
                                        <small>Премиум</small>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Информация о тарифах -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Тарифы</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Бесплатный</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>До 4 ролей</li>
                                <li><i class="fas fa-check text-success me-2"></i>Базовые команды</li>
                                <li><i class="fas fa-times text-danger me-2"></i>Свои команды</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <h6>Премиум (500 руб./мес)</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>До 10 ролей</li>
                                <li><i class="fas fa-check text-success me-2"></i>Все команды</li>
                                <li><i class="fas fa-check text-success me-2"></i>Свои команды</li>
                            </ul>
                            <a href="tariffs.php" class="btn btn-sm btn-outline-primary w-100">Подробнее</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- История платежей -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">История платежей</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($user_payments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Сумма</th>
                                            <th>Способ оплаты</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                                                <td><?php echo $payment['amount']; ?> руб.</td>
                                                <td>
                                                    <?php 
                                                    switch($payment['payment_system']) {
                                                        case 'yoomoney': echo 'ЮMoney'; break;
                                                        case 'card': echo 'Банковская карта'; break;
                                                        case 'qiwi': echo 'QIWI'; break;
                                                        default: echo $payment['payment_system'];
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge 
                                                        <?php 
                                                        switch($payment['status']) {
                                                            case 'completed': echo 'bg-success'; break;
                                                            case 'pending': echo 'bg-warning'; break;
                                                            case 'failed': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                        ?>
                                                    ">
                                                        <?php 
                                                        switch($payment['status']) {
                                                            case 'completed': echo 'Завершен'; break;
                                                            case 'pending': echo 'Ожидание'; break;
                                                            case 'failed': echo 'Ошибка'; break;
                                                            default: echo $payment['status'];
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">У вас пока нет платежей</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/payments.js"></script>
</body>
</html>