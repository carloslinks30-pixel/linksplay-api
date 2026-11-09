<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function resposta(string $status, string $message, array $extra = []): void
{
    echo json_encode(
        array_merge(['status' => $status, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$identificador = trim((string) (
    $_GET['identificador'] ??
    $_GET['mac'] ??
    $_GET['device_id'] ??
    ''
));

if ($identificador === '') {
    http_response_code(400);
    resposta('not_found', 'Identificador nao fornecido.');
}

if (strlen($identificador) > 255) {
    http_response_code(400);
    resposta('error', 'Identificador invalido.');
}

try {
    require_once __DIR__ . '/conexao.php';

    $stmt = $pdo->prepare(
        'SELECT identificador, servidor_dns, usuario, senha, ativo
         FROM clientes
         WHERE LOWER(identificador) = LOWER(:identificador)
         LIMIT 1'
    );
    $stmt->execute([':identificador' => $identificador]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        resposta('not_found', 'Dispositivo nao ativado. Informe o identificador ao administrador.');
    }

    if (isset($cliente['ativo']) && (int) $cliente['ativo'] === 0) {
        resposta('blocked', 'Dispositivo bloqueado. Contacte o suporte.');
    }

    resposta('success', 'Dispositivo ativado com sucesso.', [
        'servidor_dns' => $cliente['servidor_dns'],
        'usuario'      => $cliente['usuario'],
        'senha'        => $cliente['senha'],
    ]);
} catch (Throwable $e) {
    error_log('Erro em verificar.php: ' . $e->getMessage());
    http_response_code(500);
    resposta('error', 'Erro interno ao consultar a ativacao.');
}
