<?php
// app/Controllers/BaseController.php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @var IncomingRequest|CLIRequest
     */
    protected $request;

    protected $helpers = ['url', 'form', 'text'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    /**
     * Log de atividades reutilizável
     */
    protected function logActivity(int $userId, string $action, string $detail = ''): void
    {
        try {
            db_connect()->table('activity_logs')->insert([
                'user_id'    => $userId,
                'action'     => $action,
                'detail'     => $detail,
                'ip'         => $this->request->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'logActivity failed: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se o plano atual tem uma permissão
     */
    protected function planCan(string $permKey): bool
    {
        $planId = session()->get('plan_id');
        if (!$planId) return false;

        return db_connect()
            ->from('plan_permissions pp')
            ->join('permissions p', 'p.id = pp.permission_id')
            ->where('pp.plan_id', $planId)
            ->where('p.key', $permKey)
            ->countAllResults() > 0;
    }

    /**
     * Retorna configuração do sistema
     */
    protected function getSetting(string $key, string $default = ''): string
    {
        $row = db_connect()->from('settings')->where('key', $key)->get()->getRowArray();
        return $row['value'] ?? $default;
    }

    /**
     * Resposta JSON padronizada
     */
    protected function jsonSuccess(array $data = [], int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON(array_merge(['success' => true], $data));
    }

    protected function jsonError(string $message, int $code = 400)
    {
        return $this->response->setStatusCode($code)->setJSON(['success' => false, 'error' => $message]);
    }
}
