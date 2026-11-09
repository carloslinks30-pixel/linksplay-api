<?php
$host = 'sql310.infinityfree.com';
$db   = 'if0_43391341_farofamagica';
$user = 'if0_43391341';
$pass = 'LinksPlay123'; 
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
