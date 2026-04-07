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
        // Não autenticado → login
        if (!session()->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Faça login para continuar.');
        }

        $role   = session()->get('role');
        $status = session()->get('subscription_status');

        // Admin sempre passa
        if ($role === 'admin') return;

        // Cliente sem assinatura ativa ou trial → redireciona para upgrade
        // (não bloqueia no login — permite entrar e ver a página de upgrade)
        if ($role === 'client' && !in_array($status, ['active', 'trial'])) {
            // Permite acessar a página de upgrade sem loop
            $uri = $request->getUri()->getPath();
            $allowed = ['/app/subscription', '/app/upgrade', '/logout'];
            foreach ($allowed as $path) {
                if (str_starts_with($uri, $path)) return;
            }
            session()->setFlashdata('error', 'Sua assinatura está ' . ($status ?? 'inativa') . '. Renove ou faça upgrade do plano.');
            return redirect()->to('/app/subscription');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
