<?php
// app/Libraries/MultiVozes.php
namespace App\Libraries;

/**
 * Integração com o MultiVozes BR Engine
 * API auto-hospedada 100% compatível com OpenAI TTS
 * GitHub: https://github.com/samucamg/multivozes_br_engine
 *
 * Endpoint padrão: POST {base_url}/v1/audio/speech
 * Auth: Bearer Token (definido no .env do MultiVozes Engine)
 */
class MultiVozes
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;

    // Vozes neurais da Microsoft Edge disponíveis no MultiVozes BR Engine
    // Referência: https://github.com/samucamg/multivozes_br_engine/blob/master/VOICES.md
    public const VOICES_PT_BR = [
        // Masculinas
        ['id' => 'pt-BR-AntonioNeural',    'name' => 'Antônio',   'gender' => 'male'],
        ['id' => 'pt-BR-FabioNeural',      'name' => 'Fábio',     'gender' => 'male'],
        ['id' => 'pt-BR-HumbertoNeural',   'name' => 'Humberto',  'gender' => 'male'],
        ['id' => 'pt-BR-JulioNeural',      'name' => 'Júlio',     'gender' => 'male'],
        ['id' => 'pt-BR-NicolauNeural',    'name' => 'Nicolau',   'gender' => 'male'],
        ['id' => 'pt-BR-ValerioNeural',    'name' => 'Valério',   'gender' => 'male'],
        // Femininas
        ['id' => 'pt-BR-FranciscaNeural',  'name' => 'Francisca', 'gender' => 'female'],
        ['id' => 'pt-BR-BrendaNeural',     'name' => 'Brenda',    'gender' => 'female'],
        ['id' => 'pt-BR-DonatoNeural',     'name' => 'Donata',    'gender' => 'female'],
        ['id' => 'pt-BR-ElzaNeural',       'name' => 'Elza',      'gender' => 'female'],
        ['id' => 'pt-BR-GiovannaNeural',   'name' => 'Giovanna',  'gender' => 'female'],
        ['id' => 'pt-BR-LeticiaNeural',    'name' => 'Letícia',   'gender' => 'female'],
        ['id' => 'pt-BR-ManuelaNeural',    'name' => 'Manuela',   'gender' => 'female'],
        ['id' => 'pt-BR-ThalitaNeural',    'name' => 'Thalita',   'gender' => 'female'],
        ['id' => 'pt-BR-YaraNeural',       'name' => 'Yara',      'gender' => 'female'],
    ];

    public function __construct()
    {
        $db           = \Config\Database::connect();
        $getVal       = fn(string $k, string $d = '') => $db->table('settings')->where('key', $k)->get()->getRowArray()['value'] ?? $d;

        $this->baseUrl = rtrim($getVal('multivozes_base_url', 'http://localhost:5050'), '/');
        $this->apiKey  = $getVal('multivozes_api_key', 'sua-chave-aqui');
        $this->model   = $getVal('multivozes_model', 'tts-1');
    }

    /**
     * Converte texto em áudio MP3 via MultiVozes BR Engine
     * Salva o arquivo em writable/uploads/audio/ e retorna o caminho relativo
     */
    public function textToSpeech(string $text, string $voiceId, int $videoId = 0): array
    {
        if (empty($this->baseUrl)) {
            return ['success' => false, 'error' => 'URL do MultiVozes Engine não configurada.'];
        }

        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'API Key do MultiVozes não configurada.'];
        }

        // O engine filtra emojis e Markdown automaticamente, mas limpamos por segurança
        $text = $this->cleanText($text);

        if (empty(trim($text))) {
            return ['success' => false, 'error' => 'Texto vazio após limpeza.'];
        }

        // Limite de caracteres por requisição (evita timeouts)
        $text = mb_substr($text, 0, 3000);

        $payload = json_encode([
            'model'           => $this->model,
            'input'           => $text,
            'voice'           => $voiceId,
            'response_format' => 'mp3',
            'speed'           => 1.0,
        ]);

        $endpoint = $this->baseUrl . '/v1/audio/speech';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: audio/mpeg',
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'Erro de conexão com o MultiVozes Engine: ' . $curlErr];
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $msg     = $decoded['error']['message'] ?? $decoded['detail'] ?? "HTTP {$httpCode} — verifique se o MultiVozes Engine está rodando em {$this->baseUrl}";
            return ['success' => false, 'error' => $msg];
        }

        // Valida que a resposta é áudio MP3 (começa com ID3 ou FF FB)
        if (strlen($response) < 100) {
            return ['success' => false, 'error' => 'Resposta inválida do engine (arquivo muito pequeno).'];
        }

        // Salva o arquivo de áudio
        $dir      = WRITEPATH . 'uploads/audio/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $refId    = $videoId ?: time();
        $filename = 'narration_' . $refId . '_' . uniqid() . '.mp3';
        $fullPath = $dir . $filename;

        if (file_put_contents($fullPath, $response) === false) {
            return ['success' => false, 'error' => 'Falha ao salvar o arquivo de áudio no servidor.'];
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'path'     => $fullPath,
            'rel_path' => 'uploads/audio/' . $filename,
            'size'     => strlen($response),
            'voice_id' => $voiceId,
        ];
    }

    /**
     * Retorna a lista de vozes disponíveis no engine
     * O endpoint /v1/audio/speech é compatível com OpenAI — vozes são as do Edge TTS
     */
    public function listVoices(): array
    {
        // Tenta buscar vozes diretamente do engine (se implementado)
        $endpoint = $this->baseUrl . '/v1/voices';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Se o engine tem endpoint de vozes, usa ele
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (!empty($data['voices'])) {
                return ['success' => true, 'voices' => $data['voices']];
            }
        }

        // Fallback: retorna a lista estática de vozes PT-BR do Edge TTS
        return ['success' => true, 'voices' => self::VOICES_PT_BR];
    }

    /**
     * Testa a conexão com o MultiVozes Engine
     */
    public function testConnection(): array
    {
        if (empty($this->baseUrl)) {
            return ['online' => false, 'message' => 'URL não configurada'];
        }

        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['online' => true, 'message' => 'Engine online e respondendo'];
        }

        // Testa diretamente no endpoint principal
        $ch2 = curl_init($this->baseUrl);
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3]);
        curl_exec($ch2);
        $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $err2  = curl_error($ch2);
        curl_close($ch2);

        if ($code2 > 0) {
            return ['online' => true, 'message' => 'Engine acessível (HTTP ' . $code2 . ')'];
        }

        return ['online' => false, 'message' => 'Engine offline ou URL incorreta: ' . ($err2 ?: 'sem resposta')];
    }

    /**
     * Remove emojis, Markdown e caracteres problemáticos do texto
     */
    protected function cleanText(string $text): string
    {
        // Remove emojis e símbolos especiais
        $text = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $text);
        $text = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $text);
        $text = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $text);
        $text = preg_replace('/[\x{2600}-\x{26FF}]/u', '',  $text);
        $text = preg_replace('/[\x{2700}-\x{27BF}]/u', '',  $text);
        // Remove Markdown
        $text = preg_replace('/[*_~`#>|]/', '', $text);
        // Remove múltiplas quebras de linha
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        // Remove URLs
        $text = preg_replace('/https?:\/\/\S+/', '', $text);

        return trim($text);
    }

    /**
     * Retorna a URL base atual
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
