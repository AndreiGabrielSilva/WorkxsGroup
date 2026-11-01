<?php
// Garante que nenhum Notice (aviso) atrapalhe o layout.
error_reporting(E_ALL & ~E_NOTICE); 

// Inicia a sessão APENAS se ela não estiver ativa.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurações de conexão (Ajuste se necessário)
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "carros"; 

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// FUNÇÃO: VERIFICA SE A TABELA DE LOGIN ESTÁ VAZIA
function isLoginTableEmpty() {
    $conn = connectDB();
    // Verifica a tabela 'login'
    $result = $conn->query("SELECT COUNT(*) as count FROM login");
    if (!$result) {
        // Se a tabela não existir, assume que está vazia
        $conn->close();
        return true; 
    }
    $row = $result->fetch_assoc();
    $conn->close();
    return $row['count'] == 0;
}

// Função para verificar se o IP já está registrado na tabela 'login'
function isIPRegistered($ip) {
    $conn = connectDB();
    if (!$conn) return false;
    
    // Conta quantos registros existem para este IP
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login WHERE ip = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    
    return $count > 0; // Retorna true se encontrar 1 ou mais registros
}