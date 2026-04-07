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
        $plans = $planModel->where('is_active', 1)->orderBy('sort_order')->findAll();
        return view('auth/register', ['title' => 'Criar Conta — YT.AUTO', 'plans' => $plans]);
    }

    public function doRegister()
    {
        $rules = [
            'name'     => 'required|min_length[3]|max_length[150]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'plan_id'  => 'required|is_not_unique[plans.id]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $planModel = new PlanModel();
        $plan      = $planModel->find($this->request->getPost('plan_id'));

        $userModel = new UserModel();
        $userId    = $userModel->insert([
            'plan_id'        => $plan['id'],
            'role'           => 'client',
            'name'           => $this->request->getPost('name'),
            'email'          => $this->request->getPost('email'),
            'password_hash'  => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT, ['cost'=>12]),
            'is_active'      => 1,
            'email_verified' => 0,
        ]);

        // Cria assinatura (trial ou ativa)
        $subModel = new SubscriptionModel();
        $today    = date('Y-m-d');
        $trialEnd = $plan['trial_days'] > 0
            ? date('Y-m-d', strtotime("+{$plan['trial_days']} days"))
            : date('Y-m-d', strtotime('+30 days'));

        $subId = $subModel->insert([
            'user_id'       => $userId,
            'plan_id'       => $plan['id'],
            'billing_cycle' => 'monthly',
            'status'        => $plan['trial_days'] > 0 ? 'trial' : 'active',
            'price_paid'    => $plan['price_monthly'],
            'started_at'    => $today,
            'expires_at'    => $trialEnd,
        ]);

        // Cria primeiro pagamento pendente
        if ($plan['trial_days'] === 0) {
            db_connect()->table('payments')->insert([
                'user_id'         => $userId,
                'subscription_id' => $subId,
                'amount'          => $plan['price_monthly'],
                'method'          => 'manual',
                'status'          => 'pending',
                'due_date'        => $today,
            ]);
        }

        $this->logActivity($userId, 'register', 'Cadastro realizado — Plano: ' . $plan['name']);

        return redirect()->to('/login')->with('success', 'Conta criada! Faça login para continuar.');
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
