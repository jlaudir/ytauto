<?php
// app/Filters/ClientFilter.php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ClientFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Faça login para continuar.');
        }

        // Bloquear clientes sem plano ativo
        $role = session()->get('role');
        if ($role === 'client') {
            $status = session()->get('subscription_status');
            if (!in_array($status, ['active', 'trial'])) {
                session()->setFlashdata('error', 'Sua assinatura está ' . ($status ?: 'inativa') . '. Contate o suporte.');
                return redirect()->to('/login');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
