<?php
// O db_config.php DEVE SER O PRIMEIRO A SER INCLUÍDO para iniciar a sessão.
include 'db_config.php'; 

// Redireciona o usuário para o painel de carros
header('Location: painel_carro.php');
exit;
?>