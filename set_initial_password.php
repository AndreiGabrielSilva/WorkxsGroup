<?php
// Inclui configurações de sessão e conexão com o banco.
include 'db_config.php'; 

// Verifica se o usuário veio do index.php com a flag de novo IP
if (!isset($_SESSION['new_registration_ip'])) {
    header('Location: index.php'); // Acesso direto negado
    exit;
}

$new_ip = $_SESSION['new_registration_ip'];
$message = '';
$message_type = '';

if (isset($_POST['set_password_submit'])) {
    
    // Captura os dados de entrada (sem a constante obsoleta FILTER_SANITIZE_STRING)
    $input_login = $_POST['new_login'];
    $input_senha = $_POST['new_password'];

    if (empty($input_login) || empty($input_senha)) {
        $message = "Usuário e senha não podem ser vazios.";
        $message_type = 'error';
    } else {
        $conn = connectDB();
        
        // 1. Verifica se o login já existe na tabela 'login' (evita duplicidade de usuário principal)
        $stmt_check_login = $conn->prepare("SELECT COUNT(*) FROM login WHERE login = ?");
        $stmt_check_login->bind_param("s", $input_login);
        $stmt_check_login->execute();
        $stmt_check_login->bind_result($count_login);
        $stmt_check_login->fetch();
        $stmt_check_login->close();

        // 2. Verifica se o IP ou login já existe nas 'pendencias' (evita spam de registro)
        $stmt_check_pendencia = $conn->prepare("SELECT COUNT(*) FROM pendencias WHERE login = ? OR ip = ?");
        $stmt_check_pendencia->bind_param("ss", $input_login, $new_ip);
        $stmt_check_pendencia->execute();
        $stmt_check_pendencia->bind_result($count_pendencia);
        $stmt_check_pendencia->fetch();
        $stmt_check_pendencia->close();

        if ($count_login > 0) {
            $message = "Usuário **'".$input_login."'** já está cadastrado.";
            $message_type = 'error';
        } elseif ($count_pendencia > 0) {
            $message = "Este usuário ou IP já possui um pedido de cadastro pendente.";
            $message_type = 'error';
        } else {
            // Insere o novo usuário na tabela de PENDÊNCIAS (aguardando aprovação)
            $stmt = $conn->prepare("INSERT INTO pendencias (login, senha, ip) VALUES (?, ?, ?)");
            // 'sss' (String, String, String) para login, senha e ip
            $stmt->bind_param("sss", $input_login, $input_senha, $new_ip); 

            if ($stmt->execute()) {
                $message = "Pedido de cadastro enviado com sucesso! Aguarde a aprovação do administrador.";
                $message_type = 'success';
                
                // Limpa a flag de registro e redireciona para o login (não autentica!)
                unset($_SESSION['new_registration_ip']);
                
                // Redireciona para o index.php após 5 segundos
                header('Refresh: 5; URL=index.php');
                $stmt->close();
                $conn->close();
                exit;
            } else {
                $message = "Erro ao enviar o pedido: " . $conn->error;
                $message_type = 'error';
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workxs Group - Bem-vindo!</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico"> 
    <link rel="stylesheet" href="style.css?v=setup_1"> 
    <style>
        /* Estilo para centralizar a mensagem de boas-vindas e o formulário */
        .welcome-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        .welcome-message {
            color: #f7a0c8; /* Cor de destaque */
            margin-bottom: 20px;
            font-size: 2em;
            text-shadow: 0 0 10px rgba(247, 160, 200, 0.5); /* Sombra suave */
        }
        
        /* Estilos do formulário de criação de conta (mantidos e ajustados) */
        .setup-container {
            max-width: 400px;
            padding: 30px;
            background-color: #2a2d34;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: #e0e0e0;
        }
        .setup-container h2 {
            color: #f7a0c8;
            margin-bottom: 20px;
        }
        .setup-container .input-group input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #444;
            border-radius: 6px;
            background-color: #33363d;
            color: #fff;
        }
        .btn-set {
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
        .btn-set:hover {
            background-color: #f584b8;
        }
        .message-success { color: #4CAF50; font-weight: bold; }
        .message-error { color: #f44336; font-weight: bold; }
    </style>
</head>
<body data-theme="dark" class="login-page">
    
    <div class="welcome-center">
        
        <div class="welcome-message">
            👋 Bem-vindo ao Workxs Group!
        </div>

        <div class="setup-container">
            <h2>✨ Novo dispositivo detectado!</h2>
            
            <?php if ($message): ?>
                <p class="message-<?php echo $message_type; ?>"><?php echo $message; ?></p>
            <?php else: ?>
                <p>Seu IP (<span style="color: #f7a0c8;"><?php echo htmlspecialchars($new_ip); ?></span>) não está cadastrado. Por favor, crie uma conta.</p>
            <?php endif; ?>
            
            <?php if ($message_type !== 'success'): ?>
                <form method="POST">
                    <div class="input-group">
                        <input type="text" name="new_login" placeholder="Definir Usuário" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="new_password" placeholder="Definir Senha" required>
                    </div>
                    <button type="submit" name="set_password_submit" class="btn-set">
                        Solicitar Cadastro
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
    </div>
    
</body>
</html>