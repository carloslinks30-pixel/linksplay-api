<?php
$host = '://clever-cloud.com';
$db   = 'b4pj7w8veb27x6zglvaj';
$user = 'u6jvle60z8fclui1';
$pass = 'iSL76g2N5TGFsn1abO2D'; 
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
