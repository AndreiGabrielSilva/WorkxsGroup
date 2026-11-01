<?php
// O db_config DEVE SER O PRIMEIRO A SER INCLUÍDO para iniciar a sessão e as funções.
include 'db_config.php'; 

// --- LÓGICA DE VERIFICAÇÃO DE IP E AUTENTICAÇÃO ---
$user_ip = $_SERVER['REMOTE_ADDR'];

// Se o IP não estiver cadastrado, redireciona para a criação de conta
if (!isIPRegistered($user_ip)) {
    // Armazena o IP na sessão para que set_initial_password.php possa usá-lo
    $_SESSION['new_registration_ip'] = $user_ip;
    header('Location: set_initial_password.php');
    exit;
}

// Se o IP estiver cadastrado, o código prossegue e mostra o formulário de login.

// Exibir erro se o login falhar (vindo do painel_carros.php)
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 1) {
        $error_message = 'Usuário ou senha incorretos.';
    } elseif ($_GET['error'] == 2) {
        // MENSAGEM DE ERRO DE BLOQUEIO DE RATE LIMIT
        $error_message = 'Muitas tentativas de login falhas. Tente novamente em 5 minutos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workxs Group - Login</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico"> 
    <link rel="stylesheet" href="style.css?v=6.6"> 
    <style>
        /* CSS para o contêiner de Login */
        .login-container {
            max-width: 350px;
            /* Usamos vh para centralizar verticalmente no corpo da página */
            margin: 15vh auto 0; 
            padding: 30px;
            background-color: #2a2d34;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            text-align: center;
            color: #e0e0e0;
        }
        .login-container h1 {
            color: #f7a0c8;
            margin-bottom: 25px;
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #444;
            border-radius: 6px;
            background-color: #33363d;
            color: #fff;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #f7a0c8;
            color: #1e1e1e;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #f584b8;
        }
        .error-message {
            color: #f44336;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .info-message {
            color: #5bc0de;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* --- CSS CORRIGIDO PARA DIREITOS AUTORAIS NO CANTO INFERIOR DIREITO --- */
        .footer-copy {
            position: fixed; 
            bottom: 15px;    
            right: 15px;     
            text-align: right; 
            font-size: 0.75em;
            color: #777a82; 
            padding: 5px 10px; 
            background-color: rgba(30, 30, 30, 0.5); 
            border-radius: 5px; 
            z-index: 10;     
            white-space: nowrap; 
        }
    </style>
</head>
<body data-theme="dark" class="login-page">
    <div class="login-container">
        <h1>🔐 Login Workxs Group</h1>
        
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php else: ?>
             <p class="info-message">IP Atual Reconhecido: <?php echo htmlspecialchars($user_ip); ?></p>
        <?php endif; ?>

        <form action="painel.php" method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Usuário" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Senha" required>
            </div>
            <button type="submit" name="login_submit" class="btn-login">
                Entrar no Painel
            </button>
        </form>
    </div>
    
    <div class="footer-copy">
        &copy; <?php echo date('Y'); ?> AG Softwarer. Todos os direitos reservados.
    </div>
</body>
</html>