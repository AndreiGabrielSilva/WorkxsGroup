<?php
// Inclui configurações de sessão e conexão com o banco.
// O arquivo db_config.php deve conter session_start() e a função connectDB().
include 'db_config.php'; 

// --- 1. PROTEÇÃO DE ACESSO ---
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php'); 
    exit;
}

// --- 2. OBTÉM ID E TIPO (CRÍTICO PARA UNIFICAÇÃO) ---
$id = $_GET['id'] ?? null;
// Obtém o tipo de veículo da URL. O padrão é 'carros', mas deve vir da chamada do painel.
$tipo = strtolower($_GET['tipo'] ?? 'carros'); 

// Validação
if ($id === null || !is_numeric($id) || !in_array($tipo, ['carros', 'motos'])) {
    // Redireciona de volta para o painel específico em caso de erro
    header('Location: painel.php?tipo=' . $tipo . '&status=error&message=' . urlencode('Item ou tipo inválido para edição.'));
    exit;
}

// Define o nome da tabela com base no tipo
$table_name = $tipo;
$page_title = ucfirst($tipo);

$conn = connectDB();

// --- 3. BUSCA OS DADOS DO ITEM NA TABELA CORRETA ---
// Selecionando a nova coluna 'marca' e usando AS nome_puxar para compatibilidade
$sql_select = "SELECT id, marca, nome, `nome para puxar` AS nome_puxar, problema FROM $table_name WHERE id = ?";

$stmt = $conn->prepare($sql_select);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    // Redireciona de volta para o painel específico em caso de não encontrado
    header('Location: painel.php?tipo=' . $tipo . '&status=error&message=' . urlencode("Item (ID: $id) não encontrado na tabela $table_name."));
    exit;
}

$veiculo = $result->fetch_assoc();
$stmt->close();
$conn->close();

$message = [];
if (isset($_GET['status']) && $_GET['status'] == 'error') {
    $error_text = $_GET['message'] ?? 'Ocorreu um erro ao tentar salvar as modificações.';
    $message = ['type' => 'error', 'text' => $error_text];
}

// Define o nome do arquivo do painel que deve ser usado para voltar
$dashboard_filename = 'painel.php'; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar <?php echo $page_title; ?> #<?php echo htmlspecialchars($veiculo['id']); ?></title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body data-theme="dark"> 
    
    <div class="dashboard-container" style="max-width: 800px; margin: 50px auto; min-height: auto; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">
        
        <h1>✏️ Modificar <?php echo $page_title; ?> #<?php echo htmlspecialchars($veiculo['id']); ?></h1>
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo $message['type']; ?>-text"><?php echo $message['text']; ?></p>
        <?php endif; ?>

        <form action="processar.php?id=<?php echo htmlspecialchars($veiculo['id']); ?>&tipo=<?php echo $tipo; ?>" 
              method="POST" class="add-form" style="display: flex; flex-direction: column; gap: 15px;">
            
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($veiculo['id']); ?>">
            <input type="hidden" name="table_type" value="<?php echo $tipo; ?>">

            <h2>Informações Atuais</h2>
            <p>ID de Cadastro: **<?php echo htmlspecialchars($veiculo['id']); ?>**</p>
            
            <div class="input-group">
                <input type="text" name="marca" placeholder="Marca" 
                        value="<?php echo htmlspecialchars($veiculo['marca']); ?>" required>
            </div>
            <div class="input-group">
                <input type="text" name="nome" placeholder="Modelo" 
                        value="<?php echo htmlspecialchars($veiculo['nome']); ?>" required>
            </div>
            <div class="input-group">
                <input type="text" name="nome_para_puxar" placeholder="Nome para Puxar" 
                        value="<?php echo htmlspecialchars($veiculo['nome_puxar']); ?>" required>
            </div>
            <div class="input-group">
                <input type="text" name="problema" placeholder="Problema Reportado" 
                        value="<?php echo htmlspecialchars($veiculo['problema']); ?>">
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 30px; justify-content: space-between;">
                <button type="submit" name="update_submit" class="btn-update" style="background-color: #e74c8b; flex-grow: 1;">
                    Salvar Modificações
                </button>
                
                <a href="processar.php?action=delete&id=<?php echo htmlspecialchars($veiculo['id']); ?>&tipo=<?php echo $tipo; ?>" 
                    onclick="return confirm('Tem certeza que deseja EXCLUIR este item permanentemente (ID: <?php echo $veiculo['id']; ?> da tabela <?php echo $table_name; ?>)?');" 
                    class="logout-link btn-delete-card" style="background-color: #c0392b; text-decoration: none; flex-grow: 1; text-align: center;">
                    Excluir
                </a>
                
                <a href="<?php echo $dashboard_filename; ?>?tipo=<?php echo $tipo; ?>" class="btn-clear-search" style="background-color: #7f8c8d; text-decoration: none; flex-grow: 1; text-align: center;">
                    Voltar
                </a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            // Lê a preferência salva no localStorage (que o painel.php usa)
            const savedTheme = localStorage.getItem('theme') || 'dark'; 
            body.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>