<?php
// 1. INICIA A SESSÃO E INCLUI FUNÇÕES
// NOTA: Se db_config.php já chama session_start(), remova a linha abaixo.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_config.php'; 

// 2. VERIFICA AUTENTICAÇÃO
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// 🛑 IMPORTANTE: As linhas abaixo FORAM REMOVIDAS 🛑
/*
header('Location: painel_carros.php');
exit;
*/

// 3. DEFINE O NOME DE USUÁRIO
$username = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : 'Usuário'; 
$username_display = htmlspecialchars($username);

// 4. DEFINE O TEMPO DE REDIRECIONAMENTO
$redirect_delay = 5; 
// O destino correto é painel_carros.php, já que dashboard.php não existe no fluxo.
$redirect_target = 'painel.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workxs Group - Acesso Concedido</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico"> 
    
    <meta http-equiv="refresh" content="<?php echo $redirect_delay; ?>;url=<?php echo $redirect_target; ?>">
    
    <style>
        /* --- ESTILOS GERAIS E DE TEMA --- */
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
        .welcome-container {
            text-align: center;
            padding: 40px;
            max-width: 80%;
            position: relative;
            z-index: 10;
        }
        
        /* Ícone de Sucesso */
        .success-icon {
            font-size: 8em;
            color: #4CAF50; /* Verde de sucesso */
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
            animation: fadeInSlideUp 0.8s ease-out 1.5s forwards; /* Atraso após o ícone */
        }

        /* Subtexto */
        p {
            font-size: 1.2em;
            color: #b3b3b3;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 2s forwards; /* Atraso após o título */
        }
        
        /* Indicador de Redirecionamento */
        .redirect-text {
            margin-top: 30px;
            font-size: 0.9em;
            color: #888;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 2.5s forwards;
        }
        
        /* Animação geral de Fade-in */
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        /* Animação de Fade-in com slide */
        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* --- ANIMAÇÃO DE FUNDO SUAVE --- */
        .bg-sphere {
            position: absolute;
            width: 300px;
            height: 300px;
            background: #f7a0c8; /* Rosa Suave */
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.2;
            animation: moveSphere 20s infinite alternate;
            z-index: 5;
        }

        .bg-sphere:nth-child(2) {
            background: #a0c8f7; /* Azul Suave */
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
    
    <div class="welcome-container">
        
        <div class="success-icon">
            ✓
        </div>
        
        <h1>Acesso Autorizado, <?php echo $username_display; ?>!</h1>
        
        <p>Você será redirecionado automaticamente para o painel de gerenciamento.</p>

        <div class="redirect-text">
            Redirecionando em <span id="countdown"><?php echo $redirect_delay; ?></span> segundos...
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

        // Inicia a contagem regressiva após 3 segundos (depois que as animações principais começam)
        setTimeout(updateCountdown, 3000);
    </script>
</body>
</html>