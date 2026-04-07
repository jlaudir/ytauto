<?php
// app/Controllers/Auth.php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user_id')) {
            return redirect()->to(session()->get('role') === 'admin' ? '/admin' : '/app/dashboard');
        }
        return view('auth/login', ['title' => 'Login — YT.AUTO']);
    }

    public function doLogin()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'E-mail ou senha incorretos.')->withInput();
        }

        if (!$user['is_active']) {
            return redirect()->back()->with('error', 'Conta desativada. Contate o suporte.');
        }

        // Carrega assinatura ativa
        $subModel    = new SubscriptionModel();
        $subscription = $subModel->getActiveSubscription($user['id']);

        session()->set([
            'user_id'             => $user['id'],
            'user_name'           => $user['name'],
            'user_email'          => $user['email'],
            'role'                => $user['role'],
            'plan_id'             => $user['plan_id'],
            'subscription_status' => $subscription['status'] ?? null,
            'subscription_id'     => $subscription['id'] ?? null,
        ]);

        // Atualiza last_login
        $userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        // Log
        $this->logActivity($user['id'], 'login', 'Login realizado');

        return redirect()->to($user['role'] === 'admin' ? '/admin/dashboard' : '/app/dashboard');
    }

    public function logout()
    {
        $userId = session()->get('user_id');
        if ($userId) $this->logActivity($userId, 'logout', 'Logout realizado');
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Você saiu com sucesso.');
    }

    public function register()
    {
        if (session()->get('user_id')) return redirect()->to('/app/dashboard');
        $planModel = new PlanModel();
        // Só exibe planos pagos na tela de "Upgrade" — o cadastro é sempre gratuito
        $paidPlans = $planModel->where('is_active', 1)->where('price_monthly >', 0)->orderBy('sort_order')->findAll();
        return view('auth/register', ['title' => 'Criar Conta Grátis — YT.AUTO', 'paid_plans' => $paidPlans]);
    }

    public function doRegister()
    {
        $rules = [
            'name'     => 'required|min_length[3]|max_length[150]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        // Vincula automaticamente ao plano Free (slug = 'free')
        $planModel = new PlanModel();
        $freePlan  = $planModel->where('slug', 'free')->where('is_active', 1)->first();

        if (!$freePlan) {
            // Fallback: cria o plano free se não existir
            $freePlanId = $planModel->insert([
                'name'             => 'Free',
                'slug'             => 'free',
                'description'      => 'Plano gratuito',
                'price_monthly'    => 0.00,
                'price_annual'     => 0.00,
                'trial_days'       => 0,
                'max_videos_month' => 3,
                'max_voices'       => 1,
                'has_analytics'    => 0,
                'has_api_access'   => 0,
                'is_active'        => 1,
                'sort_order'       => 0,
            ]);
            $freePlan = $planModel->find($freePlanId);
        }

        $userModel = new UserModel();
        $userId    = $userModel->insert([
            'plan_id'        => $freePlan['id'],
            'role'           => 'client',
            'name'           => $this->request->getPost('name'),
            'email'          => $this->request->getPost('email'),
            'password_hash'  => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT, ['cost' => 12]),
            'is_active'      => 1,
            'email_verified' => 0,
        ]);

        // Assinatura free com status 'active' e sem vencimento (99 anos)
        $subModel = new SubscriptionModel();
        $subModel->insert([
            'user_id'       => $userId,
            'plan_id'       => $freePlan['id'],
            'billing_cycle' => 'monthly',
            'status'        => 'active',
            'price_paid'    => 0.00,
            'started_at'    => date('Y-m-d'),
            'expires_at'    => date('Y-m-d', strtotime('+99 years')),
            'notes'         => 'Plano gratuito — sem vencimento',
        ]);

        $this->logActivity($userId, 'register', 'Cadastro gratuito realizado');

        return redirect()->to('/login')->with('success', 'Conta criada com sucesso! Faça login para começar.');
    }

    protected function logActivity(int $userId, string $action, string $detail = ''): void
    {
        db_connect()->table('activity_logs')->insert([
            'user_id'    => $userId,
            'action'     => $action,
            'detail'     => $detail,
            'ip'         => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
