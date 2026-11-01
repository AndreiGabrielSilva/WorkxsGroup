<?php
include 'db_config.php'; 

// --- 1. CONFIGURAÇÃO DE SEGURANÇA IP FIXO ---
// O SEU IP FIXO (ou da sua máquina local, se estiver em XAMPP: "127.0.0.1")
$ADMIN_IP = "26.203.141.65"; 
$user_ip = $_SERVER['REMOTE_ADDR'];

// Se o IP do usuário NÃO for o IP do Administrador, bloqueia o acesso
if ($user_ip !== $ADMIN_IP) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acesso Negado. Esta página é restrita ao IP do Administrador.";
    exit;
}

// --- 2. LÓGICA DE PROCESSAMENTO DE APROVAÇÃO ---
if (isset($_POST['action'])) {
    $conn = connectDB();
    $id = $_POST['id'];
    
    // 2.1. Buscar dados da pendência
    $stmt_fetch = $conn->prepare("SELECT login, senha, ip FROM pendencias WHERE id = ?");
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $pendencia = $result->fetch_assoc();
    $stmt_fetch->close();

    if ($pendencia) {
        if ($_POST['action'] === 'approve') {
            // APROVAR: Mover o registro para a tabela 'login'
            $stmt_insert = $conn->prepare("INSERT INTO login (login, senha, ip) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $pendencia['login'], $pendencia['senha'], $pendencia['ip']);
            $stmt_insert->execute();
            $stmt_insert->close();
            
            $message = "Cadastro de **" . $pendencia['login'] . "** aprovado com sucesso!";

        } elseif ($_POST['action'] === 'reject') {
            $message = "Cadastro de **" . $pendencia['login'] . "** rejeitado.";
        }
        
        // Excluir a pendência da tabela 'pendencias'
        $stmt_delete = $conn->prepare("DELETE FROM pendencias WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
    } else {
        $message = "Erro: Pedido de cadastro não encontrado.";
    }
    
    $conn->close();
    header('Location: aprovar_cadastros.php?status=' . urlencode($message));
    exit;
}

// --- 3. EXIBIÇÃO DA LISTA DE PENDÊNCIAS ---
$conn = connectDB();
$sql = "SELECT id, login, ip, data_registro FROM pendencias ORDER BY data_registro ASC";
$pendencias = $conn->query($sql);
$total = $pendencias->num_rows;
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Cadastros - Workxs Group</title>
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
        .pending-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #33363d;
        }
        .pending-details { flex-grow: 1; text-align: left; }
        .pending-actions button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-approve { background-color: #4CAF50; color: white; }
        .btn-reject { background-color: #f44336; color: white; }
        .message-success { color: #4CAF50; font-weight: bold; margin-bottom: 15px; }
        .message-error { color: #f44336; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body data-theme="dark">
    <div class="admin-container">
        <h1>🔒 Painel de Aprovação de Cadastros</h1>
        <p style="margin-bottom: 20px;">Esta página é acessível apenas pelo IP: **<?php echo $ADMIN_IP; ?>**.</p>
        
        <?php if (isset($_GET['status'])): ?>
            <p class="message-success"><?php echo htmlspecialchars($_GET['status']); ?></p>
        <?php endif; ?>

        <h2>Pedidos Pendentes (<?php echo $total; ?>)</h2>
        
        <?php if ($total > 0): ?>
            <?php while($row = $pendencias->fetch_assoc()): ?>
                <div class="pending-item">
                    <div class="pending-details">
                        <p><strong>Usuário:</strong> <?php echo htmlspecialchars($row['login']); ?></p>
                        <p><strong>IP:</strong> <?php echo htmlspecialchars($row['ip']); ?></p>
                        <p style="font-size: 0.8em; color: #aaa;">Solicitado em: <?php echo date('d/m/Y H:i:s', strtotime($row['data_registro'])); ?></p>
                    </div>
                    <div class="pending-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn-approve">Aprovar ✅</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn-reject">Rejeitar ❌</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhum pedido de cadastro pendente no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>