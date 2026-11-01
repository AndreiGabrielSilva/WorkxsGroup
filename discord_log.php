<?php

/**
 * Envia uma embed de log para um webhook do Discord.
 * Agora suporta cores como: "vermelho", "verde", "amarelo" etc.
 *
 * @param string $message Mensagem da embed.
 * @param string|int $color Cor da embed (nome ou decimal).
 * @return bool
 */
function logToDiscord(string $message, $color = "amarelo"): bool {
    $falhaFile = 'discord_log_falhas.txt';

    // Webhook
    $webhookUrl = 'https://discord.com/api/webhooks/1441527520523587698/G6g50eKGO4ByBThQl-sZDxUpmpCqYbM7xTCcjVLGev7X_1UaWZWz9njr8MAbAzOhUDWK';

    // Conversor de cores
    $colorMap = [
        "vermelho" => 15158332,
        "verde"    => 3066993,
        "amarelo"  => 16776960,
        "azul"     => 3447003,
        "roxo"     => 10181046,
        "laranja"  => 15105570
    ];

    // Se foi passado um nome, converte para decimal
    if (is_string($color) && isset($colorMap[strtolower($color)])) {
        $color = $colorMap[strtolower($color)];
    }

    // Se ainda não for número, aplica amarelo por padrão
    if (!is_numeric($color)) {
        $color = $colorMap["amarelo"];
    }

    // Embed
    $data = [
        'username' => 'Sistema PHP',
        'embeds' => [
            [
                'title' => '📌 Log do Sistema',
                'description' => $message,
                'color' => (int)$color,
                'footer' => [
                    'text' => 'Log automático',
                ],
                'timestamp' => date('c'),
            ]
        ]
    ];

    $json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($webhookUrl);

    if ($ch === false) {
        file_put_contents(
            $falhaFile,
            date('Y-m-d H:i:s') . " [ERRO] Falha ao iniciar cURL\n",
            FILE_APPEND
        );
        return false;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }

    // Log de falha
    $log = date('Y-m-d H:i:s') . " [DISCORD LOG FALHA] Mensagem: '{$message}' | HTTP: {$httpCode}";
    $log .= $response === false 
        ? " | Erro cURL ($curlErrno): {$curlError}\n"
        : " | Resposta: {$response}\n";

    file_put_contents($falhaFile, $log, FILE_APPEND);

    return false;
}
