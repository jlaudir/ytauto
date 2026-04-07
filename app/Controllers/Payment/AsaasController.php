<?php
// app/Controllers/Payment/AsaasController.php
namespace App\Controllers\Payment;

use App\Controllers\BaseController;
use App\Libraries\Asaas;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use App\Models\UserModel;

class AsaasController extends BaseController
{
    /**
     * Gera cobrança PIX para uma assinatura pendente
     * Chamado pela tela de upgrade/assinatura do cliente
     */
    public function generatePix()
    {
        $userId    = session()->get('user_id');
        $paymentId = (int) $this->request->getPost('payment_id');

        // Carrega pagamento pendente do usuário
        $payModel = new PaymentModel();
        $payment  = $payModel->where(['id' => $paymentId, 'user_id' => $userId, 'status' => 'pending'])->first();

        if (!$payment) {
            return $this->response->setJSON(['error' => 'Pagamento não encontrado.'])->setStatusCode(404);
        }

        // Carrega dados do usuário
        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        $asaas    = new Asaas();
        $custResult = $asaas->getOrCreateCustomer($user);

        if (!$custResult['success']) {
            return $this->response->setJSON(['error' => 'Erro ao criar cliente no Asaas: ' . $custResult['error']])->setStatusCode(500);
        }

        // Cria cobrança PIX
        $subModel = new SubscriptionModel();
        $sub      = $subModel->find($payment['subscription_id']);
        $planName = db_connect()->table('plans')->where('id', $sub['plan_id'])->get()->getRowArray()['name'] ?? 'Assinatura';

        $pixResult = $asaas->createPixPayment([
            'customer_id'        => $custResult['customer_id'],
            'value'              => $payment['amount'],
            'due_date'           => date('Y-m-d', strtotime('+1 day')), // vence amanhã
            'description'        => "YT.AUTO — {$planName} ({$payment['id']})",
            'external_reference' => 'payment_' . $payment['id'],
        ]);

        if (!$pixResult['success']) {
            return $this->response->setJSON(['error' => $pixResult['error']])->setStatusCode(500);
        }

        // Salva referência Asaas no pagamento
        $payModel->update($paymentId, [
            'method'    => 'pix',
            'reference' => $pixResult['payment_id'],
            'notes'     => ($payment['notes'] ? $payment['notes'] . ' | ' : '') . 'Asaas PIX ID: ' . $pixResult['payment_id'],
        ]);

        return $this->response->setJSON([
            'success'     => true,
            'qr_code'     => $pixResult['qr_code'],      // base64 PNG
            'pix_copy'    => $pixResult['pix_copy'],      // copia e cola
            'expires_at'  => $pixResult['expires_at'],
            'value'       => $pixResult['value'],
            'invoice_url' => $pixResult['invoice_url'],
            'payment_id'  => $paymentId,
            'sandbox'     => $asaas->isSandbox(),
        ]);
    }

    /**
     * Webhook do Asaas — confirma pagamentos automaticamente
     * URL: /payment/asaas/webhook (configure no painel Asaas)
     */
    public function webhook()
    {
        $raw     = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!$payload || !isset($payload['event'])) {
            return $this->response->setStatusCode(400)->setBody('invalid payload');
        }

        $event   = $payload['event'];
        $payment = $payload['payment'] ?? null;

        // Registra o webhook no log
        log_message('info', "Asaas Webhook: {$event} — " . ($payment['id'] ?? 'n/a'));

        if (!$payment) return $this->response->setStatusCode(200)->setBody('ok');

        // Busca pagamento local pela referência Asaas
        $payModel = new PaymentModel();
        $local    = db_connect()->table('payments')
            ->where('reference', $payment['id'])
            ->get()->getRowArray();

        if (!$local) return $this->response->setStatusCode(200)->setBody('not found');

        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                // Confirma pagamento
                $payModel->update($local['id'], [
                    'status'  => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'notes'   => ($local['notes'] ?? '') . ' | Confirmado via Asaas Webhook em ' . date('d/m/Y H:i'),
                ]);

                // Ativa/renova assinatura
                $subModel = new SubscriptionModel();
                $sub      = $subModel->find($local['subscription_id']);
                if ($sub) {
                    $newExpiry = date('Y-m-d', strtotime($sub['expires_at'] . ' +30 days'));
                    $subModel->update($sub['id'], [
                        'status'     => 'active',
                        'expires_at' => $newExpiry,
                    ]);

                    // Atualiza plan_id do usuário
                    (new UserModel())->update($local['user_id'], ['plan_id' => $sub['plan_id']]);

                    // Cria próximo pagamento pendente
                    $payModel->insert([
                        'user_id'         => $local['user_id'],
                        'subscription_id' => $local['subscription_id'],
                        'amount'          => $local['amount'],
                        'method'          => 'pix',
                        'status'          => 'pending',
                        'due_date'        => $newExpiry,
                    ]);
                }

                db_connect()->table('activity_logs')->insert([
                    'user_id'    => $local['user_id'],
                    'action'     => 'payment_confirmed',
                    'detail'     => "PIX confirmado via Asaas — R$ " . number_format($local['amount'], 2, ',', '.'),
                    'ip'         => '0.0.0.0',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                break;

            case 'PAYMENT_OVERDUE':
                $payModel->update($local['id'], ['status' => 'pending', 'notes' => ($local['notes'] ?? '') . ' | Vencido em ' . date('d/m/Y')]);
                break;

            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_CHARGEBACK':
                $payModel->update($local['id'], ['status' => 'refunded']);
                // Suspende assinatura
                $subModel = new SubscriptionModel();
                $subModel->where('id', $local['subscription_id'])->set(['status' => 'suspended'])->update();
                break;
        }

        return $this->response->setStatusCode(200)->setBody('ok');
    }

    /**
     * Polling: verifica status de um pagamento (cliente consulta após exibir QR)
     */
    public function checkStatus(int $paymentId)
    {
        $userId = session()->get('user_id');
        $local  = db_connect()->table('payments')
            ->where(['id' => $paymentId, 'user_id' => $userId])
            ->get()->getRowArray();

        if (!$local) {
            return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);
        }

        // Já está pago localmente
        if ($local['status'] === 'paid') {
            return $this->response->setJSON(['success' => true, 'status' => 'paid', 'message' => 'Pagamento confirmado!']);
        }

        // Consulta Asaas se tem referência
        if (!empty($local['reference'])) {
            $asaas  = new Asaas();
            $result = $asaas->getPaymentStatus($local['reference']);

            if ($result['success'] && in_array($result['status'], ['RECEIVED', 'CONFIRMED'])) {
                // Confirma manualmente
                $payModel = new PaymentModel();
                $payModel->update($paymentId, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);

                // Ativa assinatura
                $subModel = new SubscriptionModel();
                $sub      = $subModel->find($local['subscription_id']);
                if ($sub) {
                    $newExpiry = date('Y-m-d', strtotime('+30 days'));
                    $subModel->update($sub['id'], ['status' => 'active', 'expires_at' => $newExpiry]);
                    (new UserModel())->update($userId, ['plan_id' => $sub['plan_id']]);

                    // Atualiza sessão
                    session()->set(['plan_id' => $sub['plan_id'], 'subscription_status' => 'active']);
                }

                return $this->response->setJSON(['success' => true, 'status' => 'paid', 'message' => '✅ Pagamento confirmado! Seu plano foi ativado.']);
            }

            return $this->response->setJSON(['success' => true, 'status' => $result['status'] ?? 'pending', 'message' => 'Aguardando pagamento...']);
        }

        return $this->response->setJSON(['success' => true, 'status' => $local['status'], 'message' => 'Aguardando...']);
    }
}
