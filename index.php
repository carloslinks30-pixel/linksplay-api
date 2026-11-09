<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/conexao.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url = 'index.php'): never
{
    header('Location: ' . $url);
    exit;
}

$adminUser = getenv('ADMIN_USER') ?: '';
$adminPass = getenv('ADMIN_PASS') ?: '';

if ($adminUser === '' || $adminPass === '') {
    http_response_code(500);
    exit('Configure ADMIN_USER e ADMIN_PASS nas variáveis de ambiente do Render.');
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$erro = '';
$sucesso = '';

if (isset($_POST['login'])) {
    $u = (string) ($_POST['usuario_admin'] ?? '');
    $p = (string) ($_POST['senha_admin'] ?? '');

    if (hash_equals($adminUser, $u) && hash_equals($adminPass, $p)) {
        session_regenerate_id(true);
        $_SESSION['autenticado'] = true;
        redirect();
    }
    $erro = 'Usuário ou senha incorretos.';
}

if (isset($_GET['sair'])) {
    $_SESSION = [];
    session_destroy();
    redirect();
}

$autenticado = !empty($_SESSION['autenticado']);

if (!$autenticado):
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LinkSPlay — Acesso administrativo</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#090d1a;color:#e5e7eb;font-family:Arial,sans-serif;padding:20px}.card{width:min(420px,100%);background:#111827;border:1px solid #263244;border-radius:18px;padding:28px;box-shadow:0 20px 60px #0008}h1{margin:0 0 8px;color:#fff}p{color:#9ca3af;margin:0 0 24px}label{display:block;margin:14px 0 6px;color:#cbd5e1;font-size:13px}input{width:100%;padding:13px;border-radius:10px;border:1px solid #334155;background:#090d1a;color:white}button{width:100%;margin-top:20px;padding:13px;border:0;border-radius:10px;background:#06b6d4;color:#00131a;font-weight:bold;cursor:pointer}.error{background:#451a1a;color:#fecaca;padding:12px;border-radius:9px;margin-bottom:14px}
</style>
</head>
<body>
<div class="card">
<h1>LinkSPlay</h1><p>Painel de gerenciamento</p>
<?php if ($erro !== ''): ?><div class="error"><?= h($erro) ?></div><?php endif; ?>
<form method="post">
<label>Usuário administrativo</label>
<input name="usuario_admin" autocomplete="username" required>
<label>Senha</label>
<input type="password" name="senha_admin" autocomplete="current-password" required>
<button type="submit" name="login">Entrar</button>
</form>
</div>
</body>
</html>
<?php
exit;
endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    $token = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals((string) $_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }

    if (isset($_POST['salvar'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $identificador = trim((string) ($_POST['identificador'] ?? ''));
        $nome = trim((string) ($_POST['nome_cliente'] ?? ''));
        $dns = trim((string) ($_POST['servidor_dns'] ?? ''));
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $senha = trim((string) ($_POST['senha'] ?? ''));

        $dnsPermitidos = [
            'http://tecno1.ink:8880',
            'http://canal231.com',
            'http://desbrave.shop',
            'http://desbravi.top',
            'http://nanoignite.click',
        ];

        if ($identificador === '' || $nome === '' || !in_array($dns, $dnsPermitidos, true)) {
            $erro = 'Preencha identificador, nome e selecione um servidor válido.';
        } elseif (strlen($identificador) > 255 || strlen($nome) > 255) {
            $erro = 'Identificador ou nome muito longo.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE clientes
                         SET identificador = :identificador,
                             nome_cliente = :nome,
                             servidor_dns = :dns,
                             usuario = :usuario,
                             senha = :senha
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':identificador' => $identificador,
                        ':nome' => $nome,
                        ':dns' => $dns,
                        ':usuario' => $usuario,
                        ':senha' => $senha,
                        ':id' => $id,
                    ]);
                    $sucesso = 'Dispositivo atualizado com sucesso.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO clientes
                         (identificador, nome_cliente, servidor_dns, usuario, senha, ativo)
                         VALUES (:identificador, :nome, :dns, :usuario, :senha, 1)'
                    );
                    $stmt->execute([
                        ':identificador' => $identificador,
                        ':nome' => $nome,
                        ':dns' => $dns,
                        ':usuario' => $usuario,
                        ':senha' => $senha,
                    ]);
                    $sucesso = 'Dispositivo cadastrado com sucesso.';
                }
            } catch (PDOException $e) {
                error_log('Erro ao salvar cliente: ' . $e->getMessage());
                $erro = 'Não foi possível salvar. Verifique se o identificador já está cadastrado e confira os logs.';
            }
        }
    }

    if (isset($_POST['excluir'])) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $sucesso = 'Dispositivo excluído.';
        }
    }

    if (isset($_POST['alternar_status'])) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE clientes SET ativo = IF(ativo = 1, 0, 1) WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $sucesso = 'Status do dispositivo atualizado.';
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT id, identificador, nome_cliente, servidor_dns, usuario, senha FROM clientes WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['editar']]);
    $editar = $stmt->fetch() ?: null;
}

$clientes = $pdo->query(
    'SELECT id, identificador, nome_cliente, servidor_dns, usuario, ativo
     FROM clientes ORDER BY id DESC'
)->fetchAll();

$total = count($clientes);
$ativos = count(array_filter($clientes, static fn($c) => (int) $c['ativo'] === 1));

$servidores = [
    'http://tecno1.ink:8880' => 'Freevis',
    'http://canal231.com' => 'Canal 231',
    'http://desbrave.shop' => 'Desbrave',
    'http://desbravi.top' => 'Desbravi',
    'http://nanoignite.click' => 'Nano Ignite',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LinkSPlay — Painel administrativo</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#090d1a;color:#e5e7eb;font-family:Arial,sans-serif}.top{background:#111827;border-bottom:1px solid #263244;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;gap:12px}.top h1{margin:0;color:#fff;font-size:21px}.top a{color:#67e8f9;text-decoration:none}.wrap{max-width:1250px;margin:auto;padding:24px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}.stat,.panel{background:#111827;border:1px solid #263244;border-radius:14px;padding:20px}.stat small{color:#9ca3af}.stat strong{display:block;font-size:27px;margin-top:8px;color:#67e8f9}.layout{display:grid;grid-template-columns:340px 1fr;gap:18px}.panel h2{font-size:17px;margin:0 0 18px}.field{margin-bottom:13px}.field label{display:block;color:#cbd5e1;font-size:12px;margin-bottom:6px}.field input,.field select,.search{width:100%;padding:11px;border:1px solid #334155;border-radius:9px;background:#090d1a;color:#fff}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;background:#06b6d4;color:#06212a;font-weight:bold;cursor:pointer;text-decoration:none}.btn.secondary{background:#334155;color:#e5e7eb}.btn.danger{background:#7f1d1d;color:#fecaca}.btn.small{padding:7px 9px;font-size:12px}.notice{padding:12px;border-radius:9px;margin-bottom:15px}.ok{background:#064e3b;color:#a7f3d0}.err{background:#7f1d1d;color:#fecaca}.tablewrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:600px}th,td{text-align:left;padding:12px;border-bottom:1px solid #263244;font-size:13px}th{color:#9ca3af}td code{color:#67e8f9;overflow-wrap:anywhere}.actions{display:flex;gap:6px;flex-wrap:wrap}.status{font-size:11px;padding:5px 8px;border-radius:20px;background:#064e3b;color:#a7f3d0}.status.off{background:#7f1d1d;color:#fecaca}.search{max-width:280px;margin-bottom:12px}@media(max-width:850px){.layout{grid-template-columns:1fr}.stats{grid-template-columns:1fr}.wrap{padding:14px}.top{padding:15px}}
</style>
</head>
<body>
<header class="top">
<h1>⚡ LinkSPlay — Gerenciamento</h1>
<a href="?sair=1">Sair</a>
</header>
<main class="wrap">
<?php if ($sucesso !== ''): ?><div class="notice ok"><?= h($sucesso) ?></div><?php endif; ?>
<?php if ($erro !== ''): ?><div class="notice err"><?= h($erro) ?></div><?php endif; ?>
<section class="stats">
<div class="stat"><small>Total de dispositivos</small><strong><?= $total ?></strong></div>
<div class="stat"><small>Dispositivos ativos</small><strong><?= $ativos ?></strong></div>
<div class="stat"><small>Dispositivos bloqueados</small><strong><?= $total - $ativos ?></strong></div>
</section>
<div class="layout">
<section class="panel">
<h2><?= $editar ? 'Editar dispositivo' : 'Nova ativação' ?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
<div class="field"><label>Identificador único *</label><input name="identificador" maxlength="255" required value="<?= h($editar['identificador'] ?? '') ?>" placeholder="MAC ou ID do dispositivo"></div>
<div class="field"><label>Nome do cliente *</label><input name="nome_cliente" maxlength="255" required value="<?= h($editar['nome_cliente'] ?? '') ?>" placeholder="Nome do cliente"></div>
<div class="field"><label>Servidor DNS *</label><select name="servidor_dns" required><option value="">Selecione</option><?php foreach ($servidores as $url => $nome): ?><option value="<?= h($url) ?>" <?= (($editar['servidor_dns'] ?? '') === $url) ? 'selected' : '' ?>><?= h($nome) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Usuário IPTV</label><input name="usuario" value="<?= h($editar['usuario'] ?? '') ?>"></div>
<div class="field"><label>Senha IPTV</label><input name="senha" value="<?= h($editar['senha'] ?? '') ?>"></div>
<button class="btn" type="submit" name="salvar">Salvar configuração</button>
<?php if ($editar): ?> <a class="btn secondary" href="index.php">Cancelar</a><?php endif; ?>
</form>
</section>
<section class="panel">
<h2>Dispositivos gerenciados</h2>
<input class="search" id="search" placeholder="Pesquisar cliente ou identificador..." oninput="filtrar()">
<div class="tablewrap">
<table id="tabela">
<thead><tr><th>#</th><th>Cliente / Identificador</th><th>Servidor</th><th>Status</th><th>Ações</th></tr></thead>
<tbody>
<?php if (!$clientes): ?><tr><td colspan="5">Nenhum dispositivo cadastrado.</td></tr><?php endif; ?>
<?php foreach ($clientes as $i => $c): ?>
<tr>
<td><?= $i + 1 ?></td>
<td><strong><?= h($c['nome_cliente']) ?></strong><br><code><?= h($c['identificador']) ?></code></td>
<td><?= h($servidores[$c['servidor_dns']] ?? $c['servidor_dns']) ?></td>
<td><span class="status <?= (int) $c['ativo'] === 1 ? '' : 'off' ?>"><?= (int) $c['ativo'] === 1 ? 'Ativo' : 'Bloqueado' ?></span></td>
<td><div class="actions">
<a class="btn small secondary" href="?editar=<?= (int) $c['id'] ?>">Editar</a>
<form method="post" onsubmit="return confirm('Alterar o status deste dispositivo?')"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn small secondary" name="alternar_status">Ativar/bloquear</button></form>
<form method="post" onsubmit="return confirm('Excluir este dispositivo?')"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn small danger" name="excluir">Excluir</button></form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
</div>
</main>
<script>
function filtrar(){
 const q=document.getElementById('search').value.toLowerCase();
 document.querySelectorAll('#tabela tbody tr').forEach(tr=>{
   tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none';
 });
}
</script>
</body>
</html>
