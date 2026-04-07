<?php
// app/Filters/Client.php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Client implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verifica se está logado
        if (!session()->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Faça login para continuar.');
        }

        // Se for admin, permite acesso
        if (session()->get('role') === 'admin') {
            return $request;
        }

        // Para clientes, verifica assinatura
        if (session()->get('role') === 'client') {
            $status = session()->get('subscription_status');
            
            // Se não tem assinatura ativa, redireciona para página de assinatura
            if (!in_array($status, ['active', 'trial'])) {
                // Evita loop redirecionando para subscription
                $currentPath = $request->getUri()->getPath();
                if ($currentPath !== '/app/subscription') {
                    return redirect()->to('/app/subscription')->with('error', 'Sua assinatura está ' . ($status ?: 'inativa') . '. Renove para continuar.');
                }
            }
        }
        
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}