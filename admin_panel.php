<?php
include 'db_config.php'; 

// --- 1. CONFIGURAÇÃO DE SEGURANÇA IP FIXO ---
// O SEU IP FIXO (ou "127.0.0.1" para testes locais)
$ADMIN_IP = "26.203.141.65"; 
$user_ip = $_SERVER['REMOTE_ADDR'];

// Se o IP do usuário NÃO for o IP do Administrador, BLOQUEIA o acesso
if ($user_ip !== $ADMIN_IP) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acesso Negado. Esta página é restrita ao IP do Administrador.";
    exit;
}

// Opcional: Contar o número de pendências para exibir um alerta no painel
$conn = connectDB();
$sql_count = "SELECT COUNT(*) AS total_pendencias FROM pendencias";
$result = $conn->query($sql_count);
$pendencias_count = $result->fetch_assoc()['total_pendencias'];
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração</title>
    <link rel="stylesheet" href="style.css?v=admin">
    <style>
        .admin-panel-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background-color: #2a2d34;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: #e0e0e0;
            text-align: center;
        }
        .admin-panel-container h1 {
            color: #f7a0c8;
            margin-bottom: 30px;
        }
        .admin-panel-links a {
            display: block;
            background-color: #33363d;
            color: #e0e0e0;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.1em;
            transition: background-color 0.3s, transform 0.1s;
            position: relative;
        }
        .admin-panel-links a:hover {
            background-color: #3e424a;
            transform: translateY(-2px);
        }
        .alert-badge {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background-color: #f44336; /* Vermelho */
            color: white;
            padding: 4px 8px;
            border-radius: 50%;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body data-theme="dark">
    <div class="admin-panel-container">
        <h1>🛠️ Painel Central de Administração</h1>
        <p style="color: #999; margin-bottom: 25px;">Acesso restrito ao IP: **<?php echo $ADMIN_IP; ?>**.</p>

        <div class="admin-panel-links">
            <a href="aprovar_cadastros.php">
                Aprovar Cadastros Pendentes
                <?php if ($pendencias_count > 0): ?>
                    <span class="alert-badge"><?php echo $pendencias_count; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="excluir_logins.php">
                Excluir Logins Ativos
            </a>
            
            <a href="dashboard.php" style="background-color: #f7a0c8; color: #1e1e1e; margin-top: 30px;">
                Voltar ao Painel Principal
            </a>
        </div>
    </div>
</body>
</html>