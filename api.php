<?php
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ========== GET ЗАПРОСЫ ==========

// Получить всех сотрудников
if ($action === 'get_employees') {
    $result = $conn->query("SELECT id, position, baseRate, login FROM employees WHERE position != 'Администратор системы'");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit();
}

// Авторизация
if ($action === 'login') {
    $login = $conn->real_escape_string($_GET['login'] ?? '');
    $password = $conn->real_escape_string($_GET['password'] ?? '');
    
    $result = $conn->query("SELECT id, position, baseRate, login FROM employees WHERE login = '$login' AND password = '$password'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "error" => "Неверный логин или пароль"]);
    }
    exit();
}

// Получить баллы сотрудника за месяц
if ($action === 'get_points') {
    $employeeId = $conn->real_escape_string($_GET['employeeId'] ?? '');
    $month = $conn->real_escape_string($_GET['month'] ?? '');
    
    $result = $conn->query("SELECT SUM(points) as total FROM points_records WHERE employeeId = '$employeeId' AND month = '$month'");
    $row = $result->fetch_assoc();
    echo json_encode(["points" => $row['total'] ?? 0]);
    exit();
}

// Получить премии сотрудника за месяц
if ($action === 'get_bonuses') {
    $employeeId = $conn->real_escape_string($_GET['employeeId'] ?? '');
    $month = $conn->real_escape_string($_GET['month'] ?? '');
    
    $result = $conn->query("SELECT SUM(amount) as total FROM cash_bonuses WHERE employeeId = '$employeeId' AND month = '$month'");
    $row = $result->fetch_assoc();
    echo json_encode(["bonus" => $row['total'] ?? 0]);
    exit();
}

// ========== POST ЗАПРОСЫ ==========

// Начислить баллы
if ($action === 'add_points') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $employeeId = $conn->real_escape_string($data['employeeId']);
    $achievementName = $conn->real_escape_string($data['achievementName']);
    $points = intval($data['points']);
    $month = $conn->real_escape_string($data['month']);
    
    $sql = "INSERT INTO points_records (employeeId, achievementName, points, month) 
            VALUES ('$employeeId', '$achievementName', $points, '$month')";
    
    if ($conn->query($sql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}

// Назначить премию
if ($action === 'add_bonus') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $employeeId = $conn->real_escape_string($data['employeeId']);
    $amount = intval($data['amount']);
    $month = $conn->real_escape_string($data['month']);
    $reason = $conn->real_escape_string($data['reason'] ?? '');
    
    $sql = "INSERT INTO cash_bonuses (employeeId, amount, month, reason) 
            VALUES ('$employeeId', $amount, '$month', '$reason')";
    
    if ($conn->query($sql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}

// Обновить ставку
if ($action === 'update_rate') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $employeeId = $conn->real_escape_string($data['employeeId']);
    $newRate = intval($data['newRate']);
    
    $sql = "UPDATE employees SET baseRate = $newRate WHERE id = '$employeeId'";
    
    if ($conn->query($sql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}

// ========== ДЕЙСТВИЯ ДЛЯ ОЧИСТКИ ДАННЫХ ==========

// Очистить все баллы
if ($action === 'clear_points') {
    $conn->query("DELETE FROM points_records");
    echo json_encode(["success" => true]);
    exit();
}

// Очистить все премии
if ($action === 'clear_bonuses') {
    $conn->query("DELETE FROM cash_bonuses");
    echo json_encode(["success" => true]);
    exit();
}

// Очистить всё
if ($action === 'clear_all') {
    $conn->query("DELETE FROM points_records");
    $conn->query("DELETE FROM cash_bonuses");
    echo json_encode(["success" => true]);
    exit();
}

// Очистить баллы конкретного сотрудника за конкретный месяц
if ($action === 'clear_points_by_employee_month') {
    $data = json_decode(file_get_contents("php://input"), true);
    $employeeId = $conn->real_escape_string($data['employeeId']);
    $month = $conn->real_escape_string($data['month']);
    $conn->query("DELETE FROM points_records WHERE employeeId = '$employeeId' AND month = '$month'");
    echo json_encode(["success" => true]);
    exit();
}

// Очистить ВСЕ баллы конкретного сотрудника
if ($action === 'clear_all_points_by_employee') {
    $data = json_decode(file_get_contents("php://input"), true);
    $employeeId = $conn->real_escape_string($data['employeeId']);
    $conn->query("DELETE FROM points_records WHERE employeeId = '$employeeId'");
    echo json_encode(["success" => true]);
    exit();
}

echo json_encode(["error" => "Неизвестное действие"]);
?>