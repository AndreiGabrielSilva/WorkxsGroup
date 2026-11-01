<?php
// Inclui configurações de sessão, conexão com o banco e funções de Rate Limiting.
include 'db_config.php'; 

// --- CONSTANTES DE RATE LIMITING (MANTIDAS) ---
define('MAX_ATTEMPTS', 5); 
define('LOCKOUT_TIME_SECONDS', 300); 

// --- FUNÇÕES DE CONTROLE DE RATE LIMITING (Adaptadas do código anterior) ---
// Estas funções dependem da tabela 'login_attempts' no seu banco de dados.

// NOTA: A função connectDB() DEVE estar definida em db_config.php.
// As funções abaixo (checkRateLimit, recordFailedAttempt, resetAttempts)
// foram mantidas para garantir a segurança no bloco de processamento de login.

function checkRateLimit($ip) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT attempts_count, last_attempt_time FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts_count'];
        $last_time = new DateTime($row['last_attempt_time']);
        $current_time = new DateTime();
        $elapsed_time = $current_time->getTimestamp() - $last_time->getTimestamp();
        $stmt->close();
        $conn->close();

        if ($attempts >= MAX_ATTEMPTS && $elapsed_time < LOCKOUT_TIME_SECONDS) {
            return true;
        }
    } else {
        $stmt->close();
        $conn->close();
    }
    return false;
}

function recordFailedAttempt($ip) {
    $conn = connectDB();
    $current_time = (new DateTime())->format('Y-m-d H:i:s');
    
    $stmt_update = $conn->prepare("UPDATE login_attempts SET attempts_count = attempts_count + 1, last_attempt_time = ? WHERE ip_address = ?");
    $stmt_update->bind_param("ss", $current_time, $ip);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows === 0) {
        $stmt_insert = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts_count, last_attempt_time) VALUES (?, 1, ?)");
        $stmt_insert->bind_param("ss", $ip, $current_time);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    
    $stmt_update->close();
    $conn->close();
}

function resetAttempts($ip) {
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
// --- FIM DAS FUNÇÕES DE RATE LIMITING ---


// --- 1. LÓGICA DE AUTENTICAÇÃO E RATE LIMITING ---
// Este bloco deve ser removido se o login for processado exclusivamente no index.php
// e o painel_carros.php for apenas a área restrita.
// Mantido para compatibilidade, mas idealmente seria movido.
if (isset($_POST['login_submit'])) {
    
    $input_login = $_POST['username'];
    $input_senha = $_POST['password'];
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if (checkRateLimit($user_ip)) {
        header('Location: index.php?error=2'); 
        exit;
    }

    $conn = connectDB(); 
    $stmt_check = $conn->prepare("SELECT id FROM login WHERE login = ? AND senha = ? AND ip = ?");
    $stmt_check->bind_param("sss", $input_login, $input_senha, $user_ip); 
    $stmt_check->execute();
    
    $stmt_check->store_result();
    $count = $stmt_check->num_rows;
    $stmt_check->close();
    $conn->close(); 

    if ($count > 0) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user_login'] = $input_login; 
        resetAttempts($user_ip); 
        // Redireciona para a tela de boas-vindas
        header('Location: welcome_logged_in.php'); 
        exit;
    } else {
        recordFailedAttempt($user_ip); 
        header('Location: index.php?error=1'); 
        exit;
    }
}

// --- 2. PROTEÇÃO DO PAINEL: Redireciona se não estiver autenticado ---
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php'); 
    exit;
}
// -----------------------------------------------------------------


// --- 3. CONFIGURAÇÃO DE TIPO DE VEÍCULO (NOVO) ---
$tipo = strtolower($_GET['tipo'] ?? 'carros'); // Padrão é 'carros'
$is_carro = ($tipo === 'carros');
$table_name = $is_carro ? 'carros' : 'motos';
$page_title = $is_carro ? 'Carros' : 'Motos';
$emoji = $is_carro ? '🚗' : '🏍️';
$emoji_small = $is_carro ? '➕' : '🛵';


// --- 4. LÓGICA DE PREFERÊNCIA DE VISUALIZAÇÃO ---
if (isset($_GET['view'])) {
    if ($_GET['view'] === 'card' || $_GET['view'] === 'table') {
        // Salva a preferência baseada no tipo de painel, para que carros e motos tenham views independentes
        $_SESSION['view_preference_' . $tipo] = $_GET['view'];
    }
    // Redireciona para limpar o parâmetro 'view' da URL
    header('Location: painel.php?tipo=' . $tipo);
    exit;
}

// Define a visualização atual (padrão é 'table')
$view_mode = $_SESSION['view_preference_' . $tipo] ?? 'table';
// --------------------------------------------------------


$conn = connectDB();

// --- 5. LÓGICA DE FILTRO/PESQUISA ---
$marcas = [];
// Prepara a busca de marcas dinamicamente
$sql_marcas = "SELECT DISTINCT marca FROM $table_name ORDER BY marca ASC"; 
$result_marcas = $conn->query($sql_marcas);
if ($result_marcas) {
    while ($row = $result_marcas->fetch_assoc()) {
        $marcas[] = htmlspecialchars($row['marca']);
    }
}

$search_term = '';
$brand_filter = '';
$where_clause = '';
$query_parts = [];

if (isset($_GET['brand_filter']) && !empty($_GET['brand_filter'])) {
    $brand_filter = $conn->real_escape_string($_GET['brand_filter']);
    $query_parts[] = " marca = '$brand_filter'";
}

if (isset($_GET['search_term']) && !empty($_GET['search_term'])) {
    $search_term = $conn->real_escape_string($_GET['search_term']);
    $query_parts[] = " (id = '$search_term' OR nome LIKE '%$search_term%' OR problema LIKE '%$search_term%')";
}

if (!empty($query_parts)) {
    $where_clause = "WHERE " . implode(" AND ", $query_parts);
}

// --- 6. EXIBIÇÃO DA TABELA DE DADOS ---
$sql = "SELECT id, marca, nome, `nome para puxar` AS nome_puxar, problema FROM $table_name $where_clause ORDER BY marca ASC, nome ASC";
$result = $conn->query($sql);

$total_results = $result ? $result->num_rows : 0;

$carros_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $carros_data[] = $row;
    }
}

$conn->close();

// Mensagem de Feedback
$message = [];
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'insert_success') {
        $message = ['type' => 'success', 'text' => "Item de $page_title cadastrado com sucesso!"];
    } elseif ($_GET['status'] == 'update_success') {
        $message = ['type' => 'success', 'text' => "Item de $page_title modificado com sucesso!"];
    } elseif ($_GET['status'] == 'error') {
        $error_text = $_GET['message'] ?? 'Ocorreu um erro na operação.';
        $message = ['type' => 'error', 'text' => $error_text];
    }
}
$active_tab_id = (!empty($brand_filter) || !empty($search_term)) ? 'search' : 'add';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Works Group - Painel de <?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico"> 
    <link rel="stylesheet" href="style.css?v=7.1"> 
    
    <style>
        /* Insira aqui os estilos de CSS unificados que definimos anteriormente para painel_carros.php
           como .view-toggle, .btn-view, .loading-overlay, .spinner, .car-card, etc.
           Abaixo estão apenas estilos essenciais ou específicos. */
        
        body {
            background-color: #1e1e1e;
            color: #e0e0e0;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        /* Estilos do Toggle e Cards de Visualização (devem ser unificados) */
        .view-toggle { display: flex; gap: 5px; margin-right: auto; }
        .btn-view { /* ... estilos ... */ }
        .btn-view.active { background-color: #f7a0c8; color: #1e1e1e; }
        .car-card-gallery { /* ... estilos ... */ }
        .car-card { /* ... estilos ... */ }
        /* Estilos do Spinner de Carga */
        .loading-overlay { /* ... estilos ... */ }
        /* ... outros estilos ... */
    </style>
</head>
<body data-theme="dark">
    
    <div id="loading-spinner" class="loading-overlay">
        <div class="spinner"></div>
        <p>Processando...</p>
    </div>
    
    <nav class="main-nav">
        <a href="painel.php?tipo=carros" class="nav-item <?php echo $is_carro ? 'active' : ''; ?>">🚗 Carros</a>
        <a href="painel.php?tipo=motos" class="nav-item <?php echo !$is_carro ? 'active' : ''; ?>">🏍️ Motos</a>
    </nav>
    <div class="dashboard-container">
        <div class="header-actions">
            <?php if (!empty($brand_filter)): ?>
                <a href="painel.php?tipo=<?php echo $tipo; ?>" class="btn-clear-search">
                    <span class="emoji">↩️</span> Limpar Filtro: **<?php echo htmlspecialchars($brand_filter); ?>**
                </a>
            <?php endif; ?>

            <div class="view-toggle">
                <a href="painel.php?tipo=<?php echo $tipo; ?>&view=table" class="btn-view <?php echo ($view_mode === 'table') ? 'active' : ''; ?>">
                    <span class="emoji">📋</span> Tabela
                </a>
                <a href="painel.php?tipo=<?php echo $tipo; ?>&view=card" class="btn-view <?php echo ($view_mode === 'card') ? 'active' : ''; ?>">
                    <span class="emoji">🖼️</span> Cards
                </a>
            </div>

            <button id="theme-toggle" class="btn-toggle-theme">
                <span class="emoji">☀️</span> Alternar Tema
            </button>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>


        <h1><?php echo $emoji; ?> Painel de Gerenciamento de <?php echo $page_title; ?></h1>
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo $message['type']; ?>-text"><?php echo $message['text']; ?></p>
        <?php endif; ?>

        <div class="form-tabs">
            <button class="tab-button <?php echo ($active_tab_id === 'add') ? 'active' : ''; ?>" onclick="openTab(event, 'add')">
                <?php echo $emoji_small; ?> ADICIONAR NOVO ITEM
            </button>
            <button class="tab-button <?php echo ($active_tab_id === 'search') ? 'active' : ''; ?>" onclick="openTab(event, 'search')">
                🖼️ FILTRAR POR MARCA
            </button>
        </div>

        <div id="add" class="tab-content <?php echo ($active_tab_id === 'add') ? 'active' : ''; ?>">
            <h2>Adicionar Nova <?php echo $page_title; ?></h2>
            <form action="processar.php?tipo=<?php echo $tipo; ?>" method="POST" class="add-form">
                <input type="hidden" name="table" value="<?php echo $table_name; ?>">
                <div class="input-group">
                    <input type="text" name="marca" placeholder="Marca (Ex: Honda)" required>
                </div>
                <div class="input-group">
                    <input type="text" name="nome" placeholder="Modelo (Ex: XRE 300)" required>
                </div>
                <div class="input-group">
                    <input type="text" name="nome_para_puxar" placeholder="Nome para Puxar (Ex: honda_xre300)" required>
                </div>
                <div class="input-group">
                    <input type="text" name="problema" placeholder="Problema Reportado (Ex: Falha na ignição)">
                </div>
                <button type="submit" name="insert_submit" class="btn-insert">Inserir <?php echo $page_title; ?></button>
            </form>
        </div>
        
        <div id="search" class="tab-content <?php echo ($active_tab_id === 'search') ? 'active' : ''; ?>">
            <h2>Escolha a Marca para Filtrar</h2>
            
            <div class="brand-gallery">
                <?php $all_class = empty($brand_filter) && empty($search_term) ? 'active' : ''; ?>
                <a href="painel.php?tipo=<?php echo $tipo; ?>" class="brand-card <?php echo $all_class; ?>">
                    <div class="brand-icon">
                        <span class="emoji all-icon">ALL</span>
                    </div>
                    <p class="brand-name">Todas as Marcas</p>
                </a>
                
                <?php 
                foreach ($marcas as $marca):
                    $img_filename = strtolower(str_replace(' ', '_', $marca)) . '.png';
                    $card_class = ($brand_filter === $marca) ? 'active' : '';
                ?>
                    <a href="painel.php?tipo=<?php echo $tipo; ?>&brand_filter=<?php echo urlencode($marca); ?>" 
                       class="brand-card <?php echo $card_class; ?>">
                        <div class="brand-icon">
                            <img src="logos/<?php echo $img_filename; ?>" alt="<?php echo $marca; ?> Logo" 
                                 onerror="this.onerror=null; this.src='logos/default.png';"> 
                        </div>
                        <p class="brand-name"><?php echo $marca; ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <hr>
            
            <form action="painel.php" method="GET" class="search-form" style="margin-top: 20px;">
                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                <input type="text" name="search_term" id="search_term_input" 
                        placeholder="Buscar Modelo, ID ou Problema (Opcional)" 
                        value="<?php echo htmlspecialchars($search_term); ?>">
                <?php if (!empty($brand_filter)): ?>
                    <input type="hidden" name="brand_filter" value="<?php echo htmlspecialchars($brand_filter); ?>">
                <?php endif; ?>
                <button type="submit" class="btn-search">Buscar Termo</button>
            </form>
        </div>


        <h2>
            <?php echo $page_title; ?> Cadastradas
            (<?php echo $total_results; ?> Resultados)
        </h2>
        
        <?php if ($total_results > 0): ?>
            
            <div class="view-content-wrapper view-<?php echo $view_mode; ?>">
                
                <?php if ($view_mode === 'table'): ?>
                    <table id="item-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Nome para Puxar</th>
                                <th>Problema</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carros_data as $row): // O array $carros_data agora contém dados de motos se tipo=motos ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['marca']); ?></td>
                                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                <td><?php echo htmlspecialchars($row['nome_puxar']); ?></td>
                                <td><?php echo htmlspecialchars($row['problema']); ?></td>
                                <td>
                                    <a href="editar.php?id=<?php echo $row['id']; ?>&tipo=<?php echo $tipo; ?>" class="btn-update">Modificar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php else: ?>
                    <div class="car-card-gallery">
                        <?php foreach ($carros_data as $row): ?>
                            <div class="car-card">
                                <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
                                <p class="card-id">ID: <?php echo $row['id']; ?></p>
                                <p>Marca: **<?php echo htmlspecialchars($row['marca']); ?>**</p>
                                <p>Código: `<?php echo htmlspecialchars($row['nome_puxar']); ?>`</p>
                                <p class="card-problem">Problema: <?php echo htmlspecialchars($row['problema']); ?></p>
                                <a href="editar.php?id=<?php echo $row['id']; ?>&tipo=<?php echo $tipo; ?>" class="btn-update-card">Modificar</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            
            </div>

        <?php else: ?>
            <p>Nenhum item encontrado com os filtros aplicados.</p>
        <?php endif; ?>

    </div>
    
    <script>
        // --- SCRIPT 1 & 2: Lógica para Abas e Temas ---
        function openTab(evt, tabName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }

            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('theme-toggle');
            const body = document.body;
            
            // Lógica de tema (mantida)
            const savedTheme = localStorage.getItem('theme') || 'dark';
            body.setAttribute('data-theme', savedTheme);

            function updateTheme(theme) {
                body.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                if (theme === 'light') {
                    toggleButton.innerHTML = '<span class="emoji">🌙</span> Alternar para Escuro';
                } else {
                    toggleButton.innerHTML = '<span class="emoji">☀️</span> Alternar para Claro';
                }
            }
            updateTheme(savedTheme);
            toggleButton.addEventListener('click', () => {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                updateTheme(newTheme);
            });
            
            // Reativa a aba de pesquisa se houver filtros aplicados
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search_term') || urlParams.get('brand_filter')) {
                document.getElementById('add').classList.remove('active');
                document.querySelector('.tab-button:nth-child(1)').classList.remove('active');
                
                document.getElementById('search').classList.add('active');
                document.querySelector('.tab-button:nth-child(2)').classList.add('active');
            }

            // --- SCRIPT 3: Lógica do Indicador de Carga (NOVO) ---
            const addForm = document.querySelector('.add-form');
            const searchForm = document.querySelector('.search-form');
            const spinner = document.getElementById('loading-spinner');
            const viewToggleLinks = document.querySelectorAll('.view-toggle a');

            function activateSpinner() {
                spinner.classList.add('active');
            }

            if (addForm) {
                addForm.addEventListener('submit', activateSpinner);
            }
            if (searchForm) {
                searchForm.addEventListener('submit', activateSpinner);
            }
            viewToggleLinks.forEach(link => {
                link.addEventListener('click', activateSpinner);
            });
        });
    </script>
</body>
</html>