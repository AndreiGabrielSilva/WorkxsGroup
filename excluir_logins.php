<?php
include 'db_config.php'; 

// --- 1. CONFIGURAÇÃO DE SEGURANÇA IP FIXO ---
// O SEU IP FIXO (ou "127.0.0.1" para testes locais)
$ADMIN_IP = "26.203.141.65"; 
$user_ip = $_SERVER['REMOTE_ADDR'];

// Se o IP do usuário NÃO for o IP do Administrador, BLOQUEIA o acesso
if ($user_ip !== $ADMIN_IP) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acesso Negado. Esta página de administração é restrita ao IP do Administrador.";
    exit;
}

// --- 2. LÓGICA DE EXCLUSÃO ---
$message = '';

if (isset($_POST['delete_login'])) {
    $conn = connectDB();
    $id_to_delete = $_POST['id'];
    
    // Evita que o usuário administrador se exclua (se souber seu próprio ID)
    // Para maior segurança, podemos adicionar uma checagem extra, mas o ID do 
    // administrador deve ser conhecido e evitado na exclusão.
    
    // Consulta para exclusão
    $stmt = $conn->prepare("DELETE FROM login WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute()) {
        $message = "Login com ID **{$id_to_delete}** excluído com sucesso!";
    } else {
        $message = "Erro ao excluir o login.";
    }
    
    $stmt->close();
    $conn->close();
    // Redireciona para atualizar a lista e exibir a mensagem
    header('Location: excluir_logins.php?status=' . urlencode($message));
    exit;
}

// --- 3. EXIBIÇÃO DA LISTA DE LOGINS ---
$conn = connectDB();
// Seleciona todos os campos, exceto a senha, por segurança
$sql = "SELECT id, login, ip FROM login ORDER BY id ASC";
$logins = $conn->query($sql);
$total = $logins->num_rows;
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Excluir Logins</title>
    <link rel="stylesheet" href="style.css?v=6.5">
    <style>
        .admin-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #2a2d34;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: #e0e0e0;
        }
        .admin-container h1 { color: #f7a0c8; }
        .login-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #33363d;
        }
        .login-details { flex-grow: 1; text-align: left; }
        .login-actions button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            background-color: #f44336; /* Vermelho para exclusão */
            color: white;
        }
        .login-actions button:hover {
             background-color: #d32f2f;
        }
        .message-status { 
            color: #f44336; 
            font-weight: bold; 
            margin-bottom: 15px; 
            background: #440000;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body data-theme="dark">
    <div class="admin-container">
        <h1>🗑️ Painel de Exclusão de Logins Ativos</h1>
        <p style="margin-bottom: 20px;">Esta página é restrita ao seu IP de administrador: **<?php echo $ADMIN_IP; ?>**.</p>
        
        <?php 
        if (isset($_GET['status'])) {
            // Exibir a mensagem de status (sucesso ou erro)
            echo '<p class="message-status">' . htmlspecialchars($_GET['status']) . '</p>';
        }
        ?>

        <h2>Contas Ativas na Tabela `login` (<?php echo $total; ?>)</h2>
        
        <?php if ($total > 0): ?>
            <?php while($row = $logins->fetch_assoc()): ?>
                <div class="login-item">
                    <div class="login-details">
                        <p><strong>ID:</strong> <?php echo $row['id']; ?></p>
                        <p><strong>Usuário:</strong> <?php echo htmlspecialchars($row['login']); ?></p>
                        <p><strong>IP Atrelado:</strong> <?php echo htmlspecialchars($row['ip']); ?></p>
                    </div>
                    <div class="login-actions">
                        <form method="POST" style="display: inline;" onsubmit="return confirm('ATENÇÃO: Você tem certeza que deseja EXCLUIR o usuário <?php echo htmlspecialchars($row['login']); ?> (ID: <?php echo $row['id']; ?>)? Esta ação é irreversível.');">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_login">Excluir</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhuma conta de login ativa na tabela.</p>
        <?php endif; ?>
    </div>
</body>
</html>