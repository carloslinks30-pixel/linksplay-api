<?php
header('Content-Type: application/json; charset=utf-8');
include_once 'conexao.php';

$identificador = trim($_GET['identificador'] ?? $_GET['mac'] ?? $_GET['device_id'] ?? '');

if (empty($identificador)) {
    echo json_encode([
        'status' => 'not_found',
        'message' => 'Identificador nao fornecido.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE LOWER(identificador) = LOWER(:identificador) LIMIT 1");
    $stmt->execute([':identificador' => $identificador]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        if (isset($cliente['ativo']) && $cliente['ativo'] == 0) {
            echo json_encode([
                'status' => 'blocked',
                'message' => 'Dispositivo bloqueado. Contacte o suporte.'
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'servidor_dns' => $cliente['servidor_dns'],
                'usuario' => $cliente['usuario'],
                'senha' => $cliente['senha'],
                'message' => 'Dispositivo ativado com sucesso.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'Dispositivo nao ativado. Informe o MAC ao administrador.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno na base de dados.'
    ]);
}
?>
