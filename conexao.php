<?php
declare(strict_types=1);

/*
 * Credenciais configuradas no Render > Environment.
 * Nunca coloque senhas diretamente neste arquivo.
 */

$host = getenv('DB_HOST') ?: '';
$db   = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

if ($host === '' || $db === '' || $user === '' || $pass === '') {
    error_log('Configuração do banco incompleta: verifique DB_HOST, DB_NAME, DB_USER e DB_PASS.');
    http_response_code(500);
    exit('Configuração do banco de dados incompleta.');
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ]
    );
} catch (PDOException $e) {
    error_log('Falha na conexão MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}
