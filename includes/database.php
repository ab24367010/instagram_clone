<?php
// database.php
// Connect to MySQL using PDO

require_once __DIR__ . '/config.php';

$charset = 'utf8mb4';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // алдааг Exception болгож гаргана
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // array-р буцаана
    PDO::ATTR_EMULATE_PREPARES   => false,                  // жинхэнэ prepared statement ашиглана
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Production-д: log руу бичиж, хэрэглэгчдэд энгийн мессеж гаргах
    error_log("Database connection failed: " . $e->getMessage(), 3, __DIR__ . '/../logs/db_errors.log');
    http_response_code(500);
    echo "Database connection failed. Please try again later.";
    exit;
}
