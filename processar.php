<?php
// --- CONFIGURAÇÃO E INCLUSÕES ---
// Inclui configurações de sessão e conexão com o banco.
include 'db_config.php'; 

// 1. INCLUIR A FUNÇÃO DE LOG (O arquivo discord_log.php deve estar na mesma pasta)
require_once 'discord_log.php'; 

// Inicia a sessão (se necessário para autenticação)
session_start();

// 1. PROTEÇÃO DE ACESSO
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php'); 
    exit;
}

// 2. IDENTIFICAÇÃO DO TIPO, TABELA E REDIRECIONAMENTO
$id = $_GET['id'] ?? null;
$tipo = strtolower($_GET['tipo'] ?? 'carros'); // Padrão é 'carros'

// Validação básica do tipo
if (!in_array($tipo, ['carros', 'motos'])) {
    header('Location: painel.php?tipo=carros&status=error&message=' . urlencode('Tipo de item inválido.'));
    exit;
}

$table_name = $tipo; // 'carros' ou 'motos'
$page_title = ucfirst($tipo); // 'Carros' ou 'Motos'
$redirect_url = 'painel.php?tipo=' . $tipo; // Redireciona de volta ao painel correto
// Variável de login para usar nas logs
$user_login = $_SESSION['user_login'] ?? 'Sistema';


// --- BLOCL 3. INSERÇÃO (CREATE) ---
if (isset($_POST['insert_submit'])) {
    
    $conn = connectDB(); 

    // Higienização dos dados
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $nome = $conn->real_escape_string($_POST['nome'] ?? '');
    $nome_puxar = $conn->real_escape_string($_POST['nome_para_puxar'] ?? '');
    $problema = $conn->real_escape_string($_POST['problema'] ?? '');

    // Consulta SQL Dinâmica: A $table_name é a tabela correta ('carros' ou 'motos')
    $sql = "INSERT INTO $table_name (marca, nome, `nome para puxar`, problema) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $marca, $nome, $nome_puxar, $problema);

    if ($stmt->execute()) {
        // Log de Sucesso (AGORA ATIVADO)
        $logMessage = "➕ ITEM INSERIDO | Usuário **$user_login** inseriu um novo $page_title: **$nome** (Marca: $marca).";
        logToDiscord($logMessage);
        
        $stmt->close();
        $conn->close();
        header('Location: ' . $redirect_url . '&status=insert_success');
        exit;
    } else {
        $error_message = "Erro ao inserir $page_title: " . $stmt->error;
        // Log de Erro (AGORA ATIVADO)
        $logMessage = "🔴 ERRO INSERÇÃO | $error_message";
        logToDiscord($logMessage); 
        
        $stmt->close();
        $conn->close();
        header('Location: ' . $redirect_url . '&status=error&message=' . urlencode($error_message));
        exit;
    }
} 
// --- FIM DA INSERÇÃO ---


// --- BLOCL 4. ATUALIZAÇÃO (UPDATE) ---
// Esta ação é chamada pelo formulário de 'editar.php'
if (isset($_POST['update_submit']) && $id !== null) {
    
    $conn = connectDB(); 

    // Higienização dos dados
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $nome = $conn->real_escape_string($_POST['nome'] ?? '');
    $nome_puxar = $conn->real_escape_string($_POST['nome_para_puxar'] ?? '');
    $problema = $conn->real_escape_string($_POST['problema'] ?? '');

    // Consulta SQL Dinâmica para UPDATE
    $sql = "UPDATE $table_name SET marca = ?, nome = ?, `nome para puxar` = ?, problema = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $marca, $nome, $nome_puxar, $problema, $id);

    if ($stmt->execute()) {
        // Log de Sucesso (AGORA ATIVADO)
        $logMessage = "🔄 ITEM ATUALIZADO | Usuário **$user_login** atualizou o $page_title (ID: $id): **$nome** (Marca: $marca).";
        logToDiscord($logMessage); 
        
        $stmt->close();
        $conn->close();
        header('Location: ' . $redirect_url . '&status=update_success');
        exit;
    } else {
        $error_message = "Erro ao atualizar $page_title (ID: $id): " . $stmt->error;
        // Log de Erro (AGORA ATIVADO)
        $logMessage = "🔴 ERRO ATUALIZAÇÃO | $error_message";
        logToDiscord($logMessage);
        
        $stmt->close();
        $conn->close();
        // Se falhar, redireciona de volta para o formulário de edição
        header('Location: editar.php?id=' . $id . '&tipo=' . $tipo . '&status=error&message=' . urlencode($error_message));
        exit;
    }
} 
// --- FIM DA ATUALIZAÇÃO ---


// --- BLOCL 5. EXCLUSÃO (DELETE) ---
// Esta ação é chamada diretamente via URL (GET) com action=delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $id !== null) {
    
    $conn = connectDB(); 

    // Opcional: Busca o nome para o log antes de excluir
    $temp_sql = "SELECT nome FROM $table_name WHERE id = ?";
    $temp_stmt = $conn->prepare($temp_sql);
    $temp_stmt->bind_param("i", $id);
    $temp_stmt->execute();
    $temp_result = $temp_stmt->get_result();
    $item_name = ($temp_result->num_rows > 0) ? $temp_result->fetch_assoc()['nome'] : "ID $id";
    $temp_stmt->close();
    
    // Consulta SQL Dinâmica para DELETE
    $sql = "DELETE FROM $table_name WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Log de Sucesso (AGORA ATIVADO)
        $logMessage = "❌ ITEM EXCLUÍDO | Usuário **$user_login** excluiu o $page_title: **$item_name** (ID: $id).";
        logToDiscord($logMessage);
        
        $stmt->close();
        $conn->close();
        header('Location: ' . $redirect_url . '&status=update_success&message=' . urlencode("$page_title '$item_name' excluído com sucesso!"));
        exit;
    } else {
        $error_message = "Erro ao excluir $page_title (ID: $id): " . $stmt->error;
        // Log de Erro (AGORA ATIVADO)
        $logMessage = "🔴 ERRO EXCLUSÃO | $error_message";
        logToDiscord($logMessage); 
        
        $stmt->close();
        $conn->close();
        header('Location: ' . $redirect_url . '&status=error&message=' . urlencode($error_message));
        exit;
    }
} 
// --- FIM DA EXCLUSÃO ---


// 6. REDIRECIONAMENTO PADRÃO
// Se o script for acessado sem submissão de formulário ou ação válida, redireciona de volta.
header('Location: ' . $redirect_url);
exit;
?>