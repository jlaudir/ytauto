<?php
// app/Libraries/Asaas.php
namespace App\Libraries;

/**
 * Integração com a API Asaas
 * Documentação: https://docs.asaas.com
 *
 * Suporta:
 *  - Criar/buscar clientes
 *  - Criar cobrança PIX com QR Code dinâmico
 *  - Consultar status de pagamento
 *  - Webhook de confirmação automática
 */
class Asaas
{
    protected string $apiKey;
    protected string $baseUrl;
    protected bool   $sandbox;

    // Endpoints
    const URL_PROD    = 'https://api.asaas.com/v3';
    const URL_SANDBOX = 'https://sandbox.asaas.com/api/v3';

    public function __construct()
    {
        $db           = \Config\Database::connect();
        $get          = fn($k, $d = '') => $db->table('settings')->where('key', $k)->get()->getRowArray()['value'] ?? $d;

        $this->apiKey  = $get('asaas_api_key', '');
        $this->sandbox = (bool)(int)$get('asaas_sandbox', '1');
        $this->baseUrl = $this->sandbox ? self::URL_SANDBOX : self::URL_PROD;
    }

    // ─── HTTP Helper ──────────────────────────────────────────────

    protected function request(string $method, string $path, array $data = []): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Asaas API Key não configurada.'];
        }

        $url = $this->baseUrl . $path;
        $ch  = curl_init($url);

        $headers = [
            'accept: application/json',
            'content-type: application/json',
            'access_token: ' . $this->apiKey,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'cURL: ' . $curlErr];
        }

        $decoded = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            $msg = $decoded['errors'][0]['description'] ?? $decoded['message'] ?? "HTTP {$httpCode}";
            return ['success' => false, 'error' => $msg, 'http_code' => $httpCode, 'raw' => $decoded];
        }

        return ['success' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }

    // ─── Clientes ─────────────────────────────────────────────────

    /**
     * Cria ou recupera cliente no Asaas pelo CPF/e-mail
     */
    public function getOrCreateCustomer(array $user): array
    {
        // Tenta encontrar pelo e-mail primeiro
        $search = $this->request('GET', '/customers?email=' . urlencode($user['email']));
        if ($search['success'] && !empty($search['data']['data'][0])) {
            return ['success' => true, 'customer_id' => $search['data']['data'][0]['id']];
        }

        // Cria novo cliente
        $payload = [
            'name'     => $user['name'],
            'email'    => $user['email'],
            'cpfCnpj'  => preg_replace('/\D/', '', $user['document'] ?? ''),
            'phone'    => preg_replace('/\D/', '', $user['phone'] ?? ''),
            'notificationDisabled' => false,
        ];

        // Remove campos vazios
        $payload = array_filter($payload, fn($v) => $v !== '');

        $result = $this->request('POST', '/customers', $payload);

        if (!$result['success']) {
            return $result;
        }

        return ['success' => true, 'customer_id' => $result['data']['id']];
    }

    // ─── Cobranças PIX ────────────────────────────────────────────

    /**
     * Cria cobrança PIX e retorna QR Code
     * Retorna: payment_id, qr_code (base64 image), pix_copy_paste, due_date, value
     */
    public function createPixPayment(array $params): array
    {
        // Parâmetros esperados:
        // customer_id, value, due_date (Y-m-d), description, external_reference
        $payload = [
            'customer'          => $params['customer_id'],
            'billingType'       => 'PIX',
            'value'             => (float) $params['value'],
            'dueDate'           => $params['due_date'],
            'description'       => $params['description'] ?? 'Assinatura YT.AUTO',
            'externalReference' => $params['external_reference'] ?? '',
        ];

        $result = $this->request('POST', '/payments', $payload);

        if (!$result['success']) {
            return $result;
        }

        $paymentId  = $result['data']['id'];
        $invoiceUrl = $result['data']['invoiceUrl'] ?? '';

        // Busca QR Code PIX
        $qrResult = $this->request('GET', '/payments/' . $paymentId . '/pixQrCode');

        if (!$qrResult['success']) {
            return [
                'success'     => true,
                'payment_id'  => $paymentId,
                'invoice_url' => $invoiceUrl,
                'qr_code'     => null,
                'pix_copy'    => null,
                'expires_at'  => $params['due_date'],
                'value'       => $params['value'],
            ];
        }

        return [
            'success'     => true,
            'payment_id'  => $paymentId,
            'invoice_url' => $invoiceUrl,
            'qr_code'     => $qrResult['data']['encodedImage']  ?? null, // base64 PNG
            'pix_copy'    => $qrResult['data']['payload']        ?? null, // copia e cola
            'expires_at'  => $qrResult['data']['expirationDate'] ?? $params['due_date'],
            'value'       => $params['value'],
            'status'      => $result['data']['status'],
        ];
    }

    /**
     * Consulta status de um pagamento
     */
    public function getPaymentStatus(string $asaasPaymentId): array
    {
        $result = $this->request('GET', '/payments/' . $asaasPaymentId);
        if (!$result['success']) return $result;

        return [
            'success'    => true,
            'status'     => $result['data']['status'],      // PENDING, RECEIVED, CONFIRMED, OVERDUE, etc.
            'value'      => $result['data']['value'],
            'paid_at'    => $result['data']['paymentDate']  ?? null,
            'net_value'  => $result['data']['netValue']     ?? null,
            'invoice_url'=> $result['data']['invoiceUrl']   ?? null,
        ];
    }

    /**
     * Cancela uma cobrança
     */
    public function cancelPayment(string $asaasPaymentId): array
    {
        return $this->request('DELETE', '/payments/' . $asaasPaymentId);
    }

    /**
     * Verifica se a API Key está válida
     */
    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['valid' => false, 'message' => 'API Key não configurada'];
        }

        $result = $this->request('GET', '/myAccount');
        if ($result['success']) {
            $name = $result['data']['name'] ?? 'N/A';
            return [
                'valid'    => true,
                'sandbox'  => $this->sandbox,
                'message'  => "Conectado: {$name}",
                'account'  => $result['data'],
            ];
        }

        return ['valid' => false, 'message' => $result['error'] ?? 'Falha na conexão'];
    }

    public function isSandbox(): bool { return $this->sandbox; }
    public function getBaseUrl(): string { return $this->baseUrl; }
}
