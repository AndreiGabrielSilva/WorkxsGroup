<?php
// Inclui o arquivo de configuração para iniciar a sessão (session_start())
include 'db_config.php'; 

// 1. LIMPAR E DESTRUIR A SESSÃO
// Remove todas as variáveis de sessão
$_SESSION = array();

// Se o cookie de sessão existir, também o destrói.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy(); 

// 2. DEFINE O TEMPO DE REDIRECIONAMENTO (Tempo para a animação rodar)
$redirect_delay = 4; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workxs Group - Desconectado</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico"> 
    
    <meta http-equiv="refresh" content="<?php echo $redirect_delay; ?>;url=index.php">
    
    <style>
        /* --- ESTILOS GERAIS --- */
        body {
            margin: 0;
            padding: 0;
            background-color: #1e1e1e;
            color: #e0e0e0;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        /* Contêiner Central */
        .logout-container {
            text-align: center;
            padding: 40px;
            max-width: 80%;
            position: relative;
            z-index: 10;
        }
        
        /* Ícone de Saída */
        .logout-icon {
            font-size: 8em;
            color: #f7a0c8; /* Rosa do tema */
            opacity: 0;
            transform: scale(0.5);
            /* Animação: Fade-in e Scale-up */
            animation: fadeInScale 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0.5s forwards;
        }

        @keyframes fadeInScale {
            to { 
                opacity: 1; 
                transform: scale(1);
            }
        }

        /* Título Principal */
        h1 {
            color: #f7a0c8;
            font-size: 3em;
            margin-bottom: 5px;
            letter-spacing: 2px;
            opacity: 0;
            animation: fadeInSlideUp 0.8s ease-out 1.5s forwards; 
        }

        /* Subtexto */
        p {
            font-size: 1.2em;
            color: #b3b3b3;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 2s forwards; 
        }
        
        /* Indicador de Redirecionamento */
        .redirect-text {
            margin-top: 30px;
            font-size: 0.9em;
            color: #888;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 2.5s forwards;
        }
        
        /* Animações Auxiliares */
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* --- ANIMAÇÃO DE FUNDO SUAVE --- */
        .bg-sphere {
            position: absolute;
            width: 300px;
            height: 300px;
            background: #f7a0c8; 
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.2;
            animation: moveSphere 20s infinite alternate;
            z-index: 5;
        }

        .bg-sphere:nth-child(2) {
            background: #a0c8f7; 
            width: 200px;
            height: 200px;
            animation: moveSphere 25s infinite alternate-reverse;
            top: 60%;
            left: 10%;
        }

        @keyframes moveSphere {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30vw, 30vh); }
        }
    </style>
</head>
<body>

    <div class="bg-sphere" style="top: 10%; left: 15%;"></div>
    <div class="bg-sphere" style="bottom: 5%; right: 5%;"></div>
    
    <div class="logout-container">
        
        <div class="logout-icon">
            🚪
        </div>
        
        <h1>Desconectado com Sucesso!</h1>
        
        <p>Obrigado por utilizar o Workxs Group.</p>

        <div class="redirect-text">
            Retornando à página de Login em <span id="countdown"><?php echo $redirect_delay; ?></span> segundos...
        </div>

    </div>

    <script>
        // --- SCRIPT DE CONTAGEM REGRESSIVA ---
        let count = <?php echo $redirect_delay; ?>;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            count--;
            if (count >= 0) {
                countdownElement.textContent = count;
                setTimeout(updateCountdown, 1000);
            }
        }

        // Inicia a contagem regressiva após 1 segundo
        setTimeout(updateCountdown, 1000);
    </script>
</body>
</html>