<?php
// app/Libraries/ElevenLabs.php
namespace App\Libraries;

/**
 * Integração com a API ElevenLabs Text-to-Speech
 */
class ElevenLabs
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.elevenlabs.io/v1';

    public function __construct()
    {
        $db  = \Config\Database::connect();
        $row = $db->table('settings')->where('key', 'elevenlabs_api_key')->get()->getRowArray();
        $this->apiKey = $row['value'] ?? '';
    }

    /**
     * Converte texto em áudio e salva o arquivo
     * Retorna o caminho relativo salvo em writable/uploads/audio/
     */
    public function textToSpeech(string $text, string $voiceId, int $videoId): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'ElevenLabs API Key não configurada.'];
        }

        // Limita texto a 2500 chars (limite free tier)
        $text = substr($text, 0, 2500);

        $payload = json_encode([
            'text'     => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability'        => 0.5,
                'similarity_boost' => 0.75,
                'style'            => 0.3,
                'use_speaker_boost' => true,
            ],
        ]);

        $ch = curl_init("{$this->baseUrl}/text-to-speech/{$voiceId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'xi-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: audio/mpeg',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error];
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            return [
                'success' => false,
                'error'   => $decoded['detail']['message'] ?? "HTTP {$httpCode}: falha na API.",
            ];
        }

        // Salva arquivo
        $dir      = WRITEPATH . 'uploads/audio/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $filename = "narration_{$videoId}_" . time() . '.mp3';
        $path     = $dir . $filename;
        file_put_contents($path, $response);

        return [
            'success'   => true,
            'filename'  => $filename,
            'path'      => $path,
            'rel_path'  => 'uploads/audio/' . $filename,
            'size'      => strlen($response),
        ];
    }

    /**
     * Lista vozes disponíveis na conta ElevenLabs
     */
    public function listVoices(): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'API Key não configurada.', 'voices' => []];
        }

        $ch = curl_init("{$this->baseUrl}/voices");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP {$httpCode}", 'voices' => []];
        }

        $data   = json_decode($response, true);
        $voices = $data['voices'] ?? [];

        return ['success' => true, 'voices' => $voices];
    }

    /**
     * Verifica saldo de caracteres da conta
     */
    public function getSubscriptionInfo(): array
    {
        if (empty($this->apiKey)) return [];

        $ch = curl_init("{$this->baseUrl}/user/subscription");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
