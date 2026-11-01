<?php
/**
 * Envia uma mensagem de log para um webhook do Discord.
 *
 * NOTA: Esta função não faz diagnóstico detalhado (escrever em arquivo) 
 * como o script de teste. Ela retorna true ou false e assume que a configuracao cURL
 * do seu ambiente XAMPP já está funcionando (o que o teste anterior provou).
 *
 * @param string $message A mensagem de log a ser enviada.
 * @return bool Retorna true em caso de envio bem-sucedido (2xx), false caso contrário.
 */
function logToDiscord(string $message): bool {
    // URL do Webhook do Discord (confirmação de que esta URL funciona)
    $webhookUrl = 'https://discord.com/api/webhooks/1441527520523587698/G6g50eKGO4ByBThQl-sZDxUpmpCqYbM7xTCcjVLGev7X_1UaWZWz9njr8MAbAzOhUDWK';

    // Conteúdo formatado para o Discord
    $data = [
        'content' => '🔔 Log: ' . $message,
        'username' => 'Sistema PHP',
    ];

    $json_data = json_encode($data);

    $ch = curl_init($webhookUrl);

    if ($ch === false) {
        // Falha na inicialização do cURL
        return false;
    }

    // Configurações
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos

    // Executa e verifica o resultado
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // O Discord retorna 204 No Content para sucesso.
    return ($httpCode >= 200 && $httpCode < 300);
}

// --- EXEMPLO DE USO ---
// logToDiscord("Usuário 'Fulano' logou com sucesso.");
// logToDiscord("Erro ao processar o formulário de contato: campo 'email' vazio.");

echo "A função logToDiscord está pronta para uso.";

?>