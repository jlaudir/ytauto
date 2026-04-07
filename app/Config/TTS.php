<?php
// URL da API Multivozes
$apiUrl = 'http://localhost:5050/v1/audio/speech';

// Chave API que você definiu no .env
$apiKey = 'TDgiSlWlF1GRRHW4ix4ziNuc9UmX/l5F7xvg+vwcw1M=';

// Exemplo de requisição
$client = \Config\Services::curlrequest();
$response = $client->post($apiUrl, [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'model' => 'tts-1',
        'input' => 'Texto a ser falado',
        'voice' => 'pt-BR-AntonioNeural',
    ],
]);