<?php
declare(strict_types=1);

$host = 'localhost';
$dbname = 'memora';
$username = 'root';
$password = '';

try {
    $bdd = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "connexion reussie";
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Connexion à la base de données indisponible.',
    ]);
    echo "connexion echouee";
    
    exit;
}
