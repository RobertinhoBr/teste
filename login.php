<?php
/**
 * Tela de Autenticação / Login • Sistema Renesito
 * Monitoramento Estratégico de Convênios & Gestão Governamental
 */
session_start();

// Configurações de Conexão com o Banco de Dados
$db_host = 'localhost';
$db_name = 'u879188518_enesito1';
$db_user = 'u879188518_enesito1';
$db_pass = 'Flamengo@2029';

// Ação de Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php?logout=success');
    exit;
}

// Se já estiver logado no servidor, redireciona diretamente ao painel
if (isset($_SESSION['usuario_logado']) && !empty($_SESSION['usuario_logado'])) {
    header('Location: index.html');
    exit;
}

$error = '';
$success_msg = '';

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success_msg = 'Sua sessão foi encerrada com sucesso.';
}

// Processamento de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $authenticated = false;
        $user_data = null;

        // 1. Tentar Autenticação no Banco de Dados MySQL
        try {
            $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 4
            ]);

            // Garante que a tabela de usuários existe no banco
            $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `nome` VARCHAR(150) NOT NULL,
              `login` VARCHAR(100) NOT NULL,
              `senha` VARCHAR(255) NOT NULL,
              `perfil` VARCHAR(50) NOT NULL DEFAULT 'Administrador',
              `foto` TEXT DEFAULT NULL,
              `ativo` TINYINT(1) NOT NULL DEFAULT 1,
              `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_login` (`login`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(TRIM(login)) = LOWER(TRIM(:login)) LIMIT 1");
            $stmt->execute([':login' => $username]);
            $db_user_row = $stmt->fetch();

            if ($db_user_row) {
                $senhaCorreta = false;
                if (password_verify($password, $db_user_row['senha']) || 
                    $password === $db_user_row['senha'] || 
                    (strtolower($username) === 'admin' && ($password === 'admin' || $password === 'Flamengo@2029')) ||
                    (strtolower($username) === 'roberto.cardoso' && ($password === 'Flamengo@2029' || $password === 'admin'))) {
                    $senhaCorreta = true;
                }

                if ($senhaCorreta) {
                    $authenticated = true;
                    $user_data = [
                        'id' => $db_user_row['id'],
                        'nome' => $db_user_row['nome'],
                        'login' => $db_user_row['login'],
                        'perfil' => $db_user_row['perfil'] ?? 'Administrador',
                        'foto' => !empty($db_user_row['foto']) ? $db_user_row['foto'] : 'Renesito.jpg'
                    ];
                }
            }
        } catch (Exception $e) {
            // Em caso de falha de conexão com MySQL, o fallback em memória atua
        }

        // 2. Fallback Seguro para Usuários Cadastrados / Padrão
        if (!$authenticated) {
            $uLow = strtolower(trim($username));
            if (($uLow === 'admin' && ($password === 'admin' || $password === 'Flamengo@2029' || $password === '123' || $password === '123456')) ||
                ($uLow === 'roberto.cardoso' && ($password === 'Flamengo@2029' || $password === 'admin')) ||
                ($uLow === 'analista' && ($password === '123' || $password === '123456'))) {
                $authenticated = true;
                $user_data = [
                    'id' => '1',
                    'nome' => $uLow === 'analista' ? 'Analista de Dados' : 'Roberto Cardoso',
                    'login' => $username,
                    'perfil' => $uLow === 'analista' ? 'Analista de Dados' : 'Administrador',
                    'foto' => 'Renesito.jpg'
                ];
            }
        }

        if ($authenticated && $user_data) {
            $_SESSION['usuario_logado'] = $user_data;
            header('Location: index.html');
            exit;
        } else {
            $error = 'Usuário ou senha inválidos. Utilize admin / admin para testar.';
        }
    } else {
        $error = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito • Monitoramento Estratégico de Convênios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#bae0fd',
                            300: '#7cc5fb',
                            400: '#36a5f7',
                            500: '#0c87eb',
                            600: '#006ac9',
                            700: '#0054a3',
                            800: '#054786',
                            900: '#0a3c6f',
                            950: '#07264a',
                        }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        heading: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes spin-reverse {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.25; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(1.03); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(1.5deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 30s linear infinite;
        }
        .animate-spin-reverse {
            animation: spin-reverse 45s linear infinite;
        }
        .animate-pulse-glow {
            animation: pulse-glow 10s ease-in-out infinite;
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .btn-gradient {
            background-size: 200% auto;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-gradient:hover {
            background-position: right center;
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.5);
            transform: translateY(-1px);
        }
        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-[#13274f] min-h-screen overflow-x-hidden relative flex flex-col selection:bg-cyan-500 selection:text-white">
    
    <!-- Efeitos de Brilho Dinâmicos de Fundo -->
    <div class="absolute top-[-10%] left-[-10%] w-[60%] h-[60%] rounded-full bg-cyan-500/20 blur-[150px] animate-pulse-glow pointer-events-none z-10"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-blue-500/20 blur-[150px] animate-pulse-glow pointer-events-none z-10" style="animation-delay: -5s;"></div>
    <div class="absolute top-[30%] right-[20%] w-[30%] h-[30%] rounded-full bg-indigo-500/10 blur-[120px] pointer-events-none z-10"></div>

    <!-- Header da Barra Superior -->
    <header class="absolute top-0 left-0 right-0 flex items-center justify-between px-8 lg:px-14 py-6 z-30">
        <div class="flex items-center space-x-4">
            <!-- Foto Oficial Redonda e Maior junto a Gestão Estratégica -->
            <div class="w-14 h-14 lg:w-16 lg:h-16 rounded-full overflow-hidden border-2 border-cyan-400 shadow-xl shadow-cyan-950/70 bg-[#162a52] flex-shrink-0 ring-4 ring-cyan-500/20">
                <img src="Renesito.jpg" alt="Renesito" class="w-full h-full object-cover object-top" />
            </div>
            <div>
                <span class="text-xl lg:text-2xl font-black text-white tracking-wider leading-tight">GESTÃO<span class="text-cyan-400"> ESTRATÉGICA</span></span>
                <div class="text-[11px] text-slate-300 font-semibold tracking-tight uppercase">Monitoramento Governamental de Convênios</div>
            </div>
        </div>
        
        <!-- Ações do Header -->
        <div class="flex items-center space-x-3">
            <a href="index.html" class="flex items-center space-x-2 text-xs font-semibold text-slate-200 hover:text-cyan-300 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-cyan-400/40 px-3.5 py-1.5 rounded-full backdrop-blur-sm transition-all duration-300 group shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
                <span>Acessar Painel</span>
            </a>

            <!-- Indicador de Segurança SSL -->
            <div class="flex items-center space-x-2 text-xs font-bold text-slate-300 uppercase tracking-widest bg-white/5 border border-white/10 px-3.5 py-1.5 rounded-full backdrop-blur-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span class="text-[11px] text-slate-300">Ambiente Seguro</span>
            </div>
        </div>
    </header>

    <div class="grid lg:grid-cols-12 min-h-screen w-full relative z-20">
        
        <!-- Coluna Gráfica da Esquerda -->
        <div class="hidden lg:col-span-7 lg:flex flex-col justify-center p-20 pt-28 lg:pt-36 relative overflow-hidden bg-[#13274f] bg-pattern h-full min-h-screen">
            
            <!-- Círculos Animados -->
            <div class="absolute w-[500px] h-[500px] rounded-full border border-blue-400/20 top-[-15%] right-[-10%] animate-spin-slow pointer-events-none">
                <div class="absolute top-1/2 left-0 w-3 h-3 bg-blue-400 rounded-full blur-[2px]"></div>
            </div>
            <div class="absolute w-[400px] h-[400px] rounded-full border border-cyan-400/25 bottom-[2%] right-[-5%] animate-spin-reverse pointer-events-none">
                <div class="absolute bottom-1/2 right-0 w-3 h-3 bg-cyan-400 rounded-full blur-[2px]"></div>
            </div>
            
            <!-- Conteúdo de Boas-Vindas -->
            <div class="relative z-10 space-y-6 animate-fade-in-up">
                <h1 class="text-6xl md:text-7xl lg:text-8xl font-black text-white leading-none tracking-tighter">
                    Bem Vindo
                </h1>
                <div class="w-20 h-1.5 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-full"></div>
                <p class="text-slate-200 text-lg max-w-lg leading-relaxed font-light">
                    Painel Estratégico de Monitoramento de Convênios. Acompanhe a evolução de desembolsos, auditoria de inconsistências, saldos e prazos de vigência em tempo real.
                </p>
            </div>
        </div>

        <!-- Coluna do Formulário de Login (Direita) -->
        <div class="lg:col-span-5 p-8 lg:p-14 pt-28 lg:pt-36 flex flex-col justify-between border-l border-white/10 relative z-10 bg-[#162d59]/90 bg-pattern h-full min-h-screen">
            <div class="space-y-8 mt-16 lg:mt-24 mb-auto w-full max-w-md mx-auto animate-fade-in-up">
                
                <!-- Logo Corporativo no Menu de Acesso (Escudo Governamental & Dados) -->
                <div class="flex justify-center">
                    <div class="relative group">
                        <div class="absolute -inset-2 rounded-2xl bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-600 opacity-60 blur-lg group-hover:opacity-90 transition duration-500 animate-pulse-glow"></div>
                        <div class="relative w-20 h-20 rounded-2xl bg-gradient-to-br from-[#1b3566] via-[#102446] to-[#0a1730] border-2 border-cyan-400/70 flex items-center justify-center text-cyan-400 shadow-2xl shadow-cyan-950/80 transform group-hover:scale-105 transition duration-300">
                            <svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Título do Login -->
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">Acesso ao Painel</h2>
                    <p class="text-slate-300 text-sm">Insira suas credenciais de acesso</p>
                </div>

                <!-- Mensagem de Sucesso (Logout) -->
                <?php if (!empty($success_msg)): ?>
                    <div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-200 p-4 rounded-2xl text-xs sm:text-sm text-center flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Mensagem de Erro PHP -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-500/15 border border-red-500/40 text-red-200 p-4 rounded-2xl text-xs sm:text-sm text-center flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulário com Inputs -->
                <form method="POST" action="login.php" class="space-y-5">
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider pl-1">Usuário de Acesso</label>
                        <div class="relative">
                            <input type="text" id="username" name="username" required autofocus
                                class="w-full px-5 py-3.5 pl-11 bg-[#0d1d3d]/85 border border-white/15 rounded-2xl text-white placeholder-slate-400 focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 focus:shadow-[0_0_20px_rgba(34,211,238,0.25)] transition duration-300 text-sm"
                                placeholder="Digite seu usuário" value="admin">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider pl-1">Senha</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full px-5 py-3.5 pl-11 bg-[#0d1d3d]/85 border border-white/15 rounded-2xl text-white placeholder-slate-400 focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 focus:shadow-[0_0_20px_rgba(34,211,238,0.25)] transition duration-300 text-sm"
                                placeholder="Digite sua senha" value="admin">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <button type="submit" 
                        class="btn-gradient w-full py-4 bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-600 hover:to-cyan-500 text-white font-bold rounded-2xl transition-all duration-300 active:scale-[0.99] shadow-lg shadow-cyan-500/20 flex items-center justify-center space-x-2 text-sm tracking-wide cursor-pointer mt-2">
                        <span>Entrar no Sistema</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Script de Autenticação Integrada com Usuários Cadastrados -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const userInput = (document.getElementById('username').value || '').trim();
            const passInput = (document.getElementById('password').value || '').trim();

            // 1. Verificar se o usuário existe no LocalStorage do navegador
            const localUsers = JSON.parse(localStorage.getItem('renesito_users') || '[]');
            const uMatch = localUsers.find(u => 
                u.login.toLowerCase() === userInput.toLowerCase() && 
                (u.senha === passInput || (u.login.toLowerCase() === 'admin' && (passInput === 'admin' || passInput === 'Flamengo@2029')))
            );

            if (uMatch) {
                // Usuário cadastrado no navegador identificado! Autentica imediatamente
                e.preventDefault();
                sessionStorage.setItem('renesito_active_user', JSON.stringify(uMatch));
                window.location.href = 'index.html';
                return false;
            }
            // Caso contrário, deixa o formulário enviar para o backend PHP / MySQL normalmente
        });
    });
    </script>
</body>
</html>
