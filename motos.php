<?php
// O db_config.php DEVE SER INCLUÍDO para iniciar a sessão e as funções de banco.
include 'db_config.php'; 

// --- 1. PROTEÇÃO DE ACESSO ---
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php'); 
    exit;
}

if (isset($_POST['insert_submit'])) {
    
    // --- 2. RECEBER E VALIDAR DADOS ---
    $marca = $_POST['marca'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $nome_puxar = $_POST['nome_para_puxar'] ?? '';
    $problema = $_POST['problema'] ?? '';

    if (empty($marca) || empty($nome) || empty($nome_puxar)) {
        // Redireciona de volta para o painel de motos com erro
        header('Location: painel_motos.php?status=error&msg=Campos obrigatórios vazios.');
        exit;
    }

    // --- 3. CONEXÃO E PREPARAÇÃO DA INSERÇÃO ---
    $conn = connectDB(); 

    // *** MUDANÇA AQUI: Tabela 'motos' ***
    $sql = "INSERT INTO motos (marca, nome, `nome para puxar`, problema) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $marca, $nome, $nome_puxar, $problema);
    
    // --- 4. EXECUTAR E REDIRECIONAR ---
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redireciona para o painel de motos com sucesso
        header('Location: painel_motos.php?status=insert_success'); 
        exit;
    } else {
        error_log("Erro de inserção SQL na tabela motos: " . $stmt->error); 
        $stmt->close();
        $conn->close();
        header('Location: painel_motos.php?status=error&msg=Falha ao inserir moto no banco.');
        exit;
    }

} else {
    // Redireciona se acessado diretamente
    header('Location: painel_motos.php');
    exit;
}
?>