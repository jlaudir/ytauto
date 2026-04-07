<?php
// app/Filters/AdminFilter.php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Admin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Acesso restrito.');
        }
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/app/dashboard')->with('error', 'Sem permissão de administrador.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
