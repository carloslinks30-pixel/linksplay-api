<?php
include_once 'conexao.php';

$msg = "";
$msg_tipo = "";

// Salvar ou Atualizar Cliente
if (isset($_POST['salvar'])) {
    $identificador = trim($_POST['identificador']);
    $nome = trim($_POST['nome_cliente']);
    $dns = trim($_POST['servidor_dns']);
    $user = trim($_POST['usuario']);
    $pass = trim($_POST['senha']);

    if (!empty($identificador) && !empty($nome) && !empty($dns)) {
        try {
            $sql = "INSERT INTO clientes (identificador, nome_cliente, servidor_dns, usuario, senha, ativo) 
                    VALUES (:identificador, :nome, :dns, :user, :pass, 1)
                    ON DUPLICATE KEY UPDATE nome_cliente=:nome, servidor_dns=:dns, usuario=:user, senha=:pass, ativo=1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':identificador' => $identificador, 
                ':nome' => $nome, 
                ':dns' => $dns, 
                ':user' => $user, 
                ':pass' => $pass
            ]);
            $msg = "Dispositivo processado com sucesso!";
            $msg_tipo = "sucesso";
        } catch (PDOException $e) {
            $msg = "Erro ao salvar: " . $e->getMessage();
            $msg_tipo = "erro";
        }
    } else {
        $msg = "Preencha todos os campos obrigatórios.";
        $msg_tipo = "erro";
    }
}

// Excluir Cliente - Aponta para index.php
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// Estatísticas e Listagem
$total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkSPlay Pro — Dashboard Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darker: '#090D1A',
                        dark: '#111827',
                        card: '#1F2937',
                        inputbg: '#111827',
                        accent: '#06B6D4',
                        accentHover: '#0891B2'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-image: radial-gradient(circle at top right, rgba(6,182,212,0.05), transparent 40%); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #111827; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #06B6D4; }
    </style>
</head>
<body class="bg-darker text-slate-100 min-h-screen antialiased selection:bg-cyan-500 selection:text-white">

    <!-- Navbar Superior -->
    <header class="border-b border-slate-800 bg-dark/70 backdrop-blur-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-cyan-500/30 ring-1 border border-cyan-400/20">
                    <i class="fa-solid fa-bolt text-white text-lg animate-pulse"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg tracking-wide text-white flex items-center">LinkSPlay <span class="text-cyan-400 ml-1">Pro</span></h1>
                    <p class="text-xs text-slate-400">Gerenciamento Avançado de Licenças</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-400 animate-ping"></span> Servidor Ativo
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Notificações Dinâmicas -->
        <?php if(!empty($msg)): ?>
            <div id="toast-msg" class="mb-6 p-4 rounded-xl border flex items-center justify-between transition-all duration-300 <?= $msg_tipo === 'sucesso' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-rose-500/10 border-rose-500/30 text-rose-400' ?>">
                <div class="flex items-center space-x-3 text-sm font-medium">
                    <i class="fa-solid <?= $msg_tipo === 'sucesso' ? 'fa-circle-check text-lg' : 'fa-triangle-exclamation text-lg' ?>"></i>
                    <span><?= $msg ?></span>
                </div>
                <button onclick="document.getElementById('toast-msg').remove()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- Cards Estatísticos -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-dark/50 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-cyan-500/40 transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total de Conexões</p>
                        <h3 class="text-3xl font-extrabold text-white mt-1"><?= $total_clientes ?></h3>
                    </div>
                    <div class="p-3 bg-cyan-500/10 rounded-xl text-cyan-400 border border-cyan-500/20 shadow-inner">
                        <i class="fa-solid fa-display text-xl"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-cyan-500 to-blue-500 opacity-60 group-hover:opacity-100 transition-opacity"></div>
            </div>

            <div class="bg-dark/50 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-indigo-500/40 transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Gateways Ativos</p>
                        <h3 class="text-3xl font-extrabold text-white mt-1">5</h3>
                    </div>
                    <div class="p-3 bg-indigo-500/10 rounded-xl text-indigo-400 border border-indigo-500/20">
                        <i class="fa-solid fa-server text-xl"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-indigo-500 to-purple-500 opacity-60 group-hover:opacity-100 transition-opacity"></div>
            </div>

            <div class="bg-dark/50 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-purple-500/40 transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sistemas Compatíveis</p>
                        <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-purple-400 mt-2">Multi-Platform</h3>
                    </div>
                    <div class="p-3 bg-purple-500/10 rounded-xl text-purple-400 border border-purple-500/20">
                        <i class="fa-solid fa-mobile-screen text-xl"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-purple-500 to-pink-500 opacity-60 group-hover:opacity-100 transition-opacity"></div>
            </div>
        </div>

        <!-- Seção Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Painel de Gerenciamento / Formulário - Submissão para index.php -->
            <div class="lg:col-span-1">
                <div class="bg-dark border border-slate-800 rounded-2xl p-6 shadow-2xl sticky top-24 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-cyan-500/10 rounded-lg text-cyan-400">
                                <i id="form-icon" class="fa-solid fa-user-plus"></i>
                            </div>
                            <h2 id="form-title" class="font-bold text-base text-white">Nova Ativação</h2>
                        </div>
                        <button type="button" id="btn-cancel-edit" onclick="resetForm()" class="hidden text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded-md transition-colors">Cancelar</button>
                    </div>

                    <form action="index.php" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Identificador Único <span class="text-rose-500">*</span></label>
                            <input type="text" id="in_identificador" name="identificador" placeholder="Ex: MAC ou ID Persistente" required 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nome do Cliente <span class="text-rose-500">*</span></label>
                            <input type="text" id="in_nome" name="nome_cliente" placeholder="Ex: João Silva (PC)" required 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Servidor DNS Principal <span class="text-rose-500">*</span></label>
                            <select id="in_dns" name="servidor_dns" required 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                <option value="">Selecione o Servidor</option>
                                <option value="http://tecno1.ink:8880">Freevis</option>
                                <option value="http://canal231.com">Canal 231</option>
                                <option value="http://desbrave.shop">Desbrave</option>
                                <option value="http://desbravi.top">Desbravi</option>
                                <option value="http://nanoignite.click">Nano Ignite</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Usuário</label>
                            <input type="text" id="in_usuario" name="usuario" placeholder="Utilizador IPTV" 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Senha</label>
                            <input type="text" id="in_senha" name="senha" placeholder="Palavra-passe IPTV" 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                        </div>

                        <button type="submit" name="salvar" class="w-full mt-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-cyan-500/25 hover:from-cyan-400 hover:to-blue-500 transition-all transform active:scale-95 flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-check"></i>
                            <span id="btn-text">Salvar Configuração</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabela de Listagem -->
            <div class="lg:col-span-2">
                <div class="bg-dark border border-slate-800 rounded-2xl shadow-2xl overflow-hidden backdrop-blur-sm">
                    <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-3 w-full sm:w-auto">
                            <div class="p-2 bg-blue-500/10 rounded-lg text-blue-400">
                                <i class="fa-solid fa-table-list"></i>
                            </div>
                            <h2 class="font-bold text-base text-white">Dispositivos Gerenciados</h2>
                        </div>
                        <div class="w-full sm:w-72 relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" id="table-search" onkeyup="filterTable()" placeholder="Pesquisar cliente ou MAC..." 
                                class="w-full bg-inputbg border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400 transition-all">
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900/60 text-slate-400 text-xs font-semibold uppercase tracking-wider border-b border-slate-800">
                                    <th class="py-4 px-6 w-16">#</th>
                                    <th class="py-4 px-6">Cliente / Identificador</th>
                                    <th class="py-4 px-6">Servidor DNS</th>
                                    <th class="py-4 px-6">Acesso (User/Pass)</th>
                                    <th class="py-4 px-6 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-sm">
                                <?php if(empty($clientes)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-regular fa-folder-open text-3xl text-slate-600"></i>
                                            <p>Nenhum dispositivo cadastrado no momento.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php $contador = 1; foreach ($clientes as $c): ?>
                                    <tr class="row-item hover:bg-slate-800/30 transition-colors">
                                        <td class="py-4 px-6 text-slate-400 font-mono text-xs">
                                            <?= $contador++ ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-medium text-white data-nome"><?= htmlspecialchars($c['nome_cliente']) ?></div>
                                            <code class="text-xs text-cyan-400 font-mono data-identificador mt-0.5 inline-block"><?= htmlspecialchars($c['identificador']) ?></code>
                                            <span class="data-user hidden"><?= htmlspecialchars($c['usuario']) ?></span>
                                            <span class="data-pass hidden"><?= htmlspecialchars($c['senha']) ?></span>
                                        </td>
                                        <td class="py-4 px-6 text-slate-300 text-xs data-dns truncate max-w-[180px]" title="<?= htmlspecialchars($c['servidor_dns']) ?>">
                                            <?= htmlspecialchars($c['servidor_dns']) ?>
                                        </td>
                                        <td class="py-4 px-6 text-slate-300 text-xs font-mono">
                                            <?php if(!empty($c['usuario'])): ?>
                                                <span class="text-cyan-300"><?= htmlspecialchars($c['usuario']) ?></span> / <span class="text-slate-400"><?= htmlspecialchars($c['senha']) ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-600 italic">Não informado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <button type="button" onclick="editDevice(this)" title="Editar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500 hover:text-white transition-all border border-cyan-500/25">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <a href="index.php?excluir=<?= $c['id'] ?>" onclick="return confirm('Deseja realmente excluir este dispositivo?')" title="Excluir" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white transition-all border border-rose-500/25">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function editDevice(element) {
            const row = element.closest('.row-item');
            const nome = row.querySelector('.data-nome').innerText;
            const identificador = row.querySelector('.data-identificador').innerText;
            const dns = row.querySelector('.data-dns').innerText;
            const user = row.querySelector('.data-user').innerText;
            const pass = row.querySelector('.data-pass').innerText;

            document.getElementById('in_nome').value = nome;
            document.getElementById('in_identificador').value = identificador;
            document.getElementById('in_dns').value = dns.trim();
            document.getElementById('in_usuario').value = user;
            document.getElementById('in_senha').value = pass;

            document.getElementById('form-title').innerText = "Atualizar Dispositivo";
            document.getElementById('form-icon').className = "fa-solid fa-user-gear text-cyan-400";
            document.getElementById('btn-text').innerText = "Salvar Alterações";
            document.getElementById('btn-cancel-edit').classList.remove('hidden');
            document.getElementById('in_identificador').focus();
        }

        function resetForm() {
            document.getElementById('in_nome').value = "";
            document.getElementById('in_identificador').value = "";
            document.getElementById('in_dns').value = "";
            document.getElementById('in_usuario').value = "";
            document.getElementById('in_senha').value = "";

            document.getElementById('form-title').innerText = "Nova Ativação";
            document.getElementById('form-icon').className = "fa-solid fa-user-plus";
            document.getElementById('btn-text').innerText = "Salvar Configuração";
            document.getElementById('btn-cancel-edit').classList.add('hidden');
        }

        function filterTable() {
            const query = document.getElementById('table-search').value.toLowerCase();
            const rows = document.querySelectorAll('.row-item');

            rows.forEach(row => {
                const txtNome = row.querySelector('.data-nome').innerText.toLowerCase();
                const txtId = row.querySelector('.data-identificador').innerText.toLowerCase();
                const txtDns = row.querySelector('.data-dns').innerText.toLowerCase();

                if(txtNome.includes(query) || txtId.includes(query) || txtDns.includes(query)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
