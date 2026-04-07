<?php
// app/Controllers/Client/Profile.php
namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;
use App\Models\PlanModel;

class Profile extends BaseController
{
    public function index()
    {
        $userId = session()->get('user_id');
        $user   = (new UserModel())->getWithPlan($userId);
        return view('client/profile', ['title' => 'Meu Perfil', 'user' => $user]);
    }

    public function update()
    {
        $userId = session()->get('user_id');
        $model  = new UserModel();
        $data   = [
            'name'  => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
        ];
        $newPass = $this->request->getPost('password');
        if (!empty($newPass)) {
            if (strlen($newPass) < 8) {
                return redirect()->back()->with('error', 'A senha deve ter ao menos 8 caracteres.');
            }
            $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
        }
        $model->update($userId, $data);
        session()->set('user_name', $data['name']);
        return redirect()->back()->with('success', 'Perfil atualizado!');
    }

    public function subscription()
    {
        $userId   = session()->get('user_id');
        $sub      = (new SubscriptionModel())->getActiveSubscription($userId);
        $plan     = $sub ? (new PlanModel())->find($sub['plan_id']) : null;
        $payments = (new PaymentModel())->listWithDetails(['user_id' => $userId]);
        $isFree   = $plan && $plan['price_monthly'] == 0;

        return view('client/subscription', [
            'title'        => 'Minha Assinatura',
            'subscription' => $sub,
            'plan'         => $plan,
            'payments'     => $payments,
            'is_free'      => $isFree,
        ]);
    }

    /**
     * Página de escolha/upgrade de plano
     */
    public function upgrade()
    {
        $planModel  = new PlanModel();
        $paidPlans  = $planModel->where('is_active', 1)->where('price_monthly >', 0)->orderBy('sort_order')->findAll();
        $currentPlanId = session()->get('plan_id');

        return view('client/upgrade', [
            'title'          => 'Escolher Plano',
            'paid_plans'     => $paidPlans,
            'current_plan_id'=> $currentPlanId,
        ]);
    }

    /**
     * Processa a solicitação de upgrade (cria nova assinatura pendente de confirmação pelo admin)
     */
    public function doUpgrade()
    {
        $planId    = (int) $this->request->getPost('plan_id');
        $userId    = session()->get('user_id');
        $planModel = new PlanModel();
        $newPlan   = $planModel->find($planId);

        if (!$newPlan || $newPlan['price_monthly'] == 0) {
            return redirect()->back()->with('error', 'Plano inválido.');
        }

        // Registra a solicitação de upgrade no log — admin confirma o pagamento
        db_connect()->table('activity_logs')->insert([
            'user_id'    => $userId,
            'action'     => 'upgrade_request',
            'detail'     => 'Solicitação de upgrade para o plano: ' . $newPlan['name'] . ' (R$ ' . number_format($newPlan['price_monthly'], 2, ',', '.') . '/mês)',
            'ip'         => $this->request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Cria assinatura com status 'suspended' aguardando confirmação do pagamento
        $trialDays  = (int) $newPlan['trial_days'];
        $subModel   = new SubscriptionModel();
        $today      = date('Y-m-d');
        $expiresAt  = $trialDays > 0
            ? date('Y-m-d', strtotime('+' . $trialDays . ' days'))
            : date('Y-m-d', strtotime('+30 days'));

        $status = $trialDays > 0 ? 'trial' : 'suspended';

        $subId = $subModel->insert([
            'user_id'       => $userId,
            'plan_id'       => $planId,
            'billing_cycle' => $this->request->getPost('billing_cycle') ?? 'monthly',
            'status'        => $status,
            'price_paid'    => $newPlan['price_monthly'],
            'started_at'    => $today,
            'expires_at'    => $expiresAt,
            'notes'         => 'Aguardando confirmação de pagamento',
        ]);

        // Cria pagamento pendente para o admin processar
        if ($trialDays === 0) {
            (new PaymentModel())->insert([
                'user_id'         => $userId,
                'subscription_id' => $subId,
                'amount'          => $newPlan['price_monthly'],
                'method'          => 'manual',
                'status'          => 'pending',
                'due_date'        => $today,
                'notes'           => 'Upgrade solicitado pelo usuário',
            ]);
        } else {
            // Trial: ativa imediatamente
            $subModel->update($subId, ['status' => 'trial']);
            // Atualiza plan_id do usuário e sessão
            (new UserModel())->update($userId, ['plan_id' => $planId]);
            session()->set(['plan_id' => $planId, 'subscription_status' => 'trial']);
            return redirect()->to('/app/dashboard')->with('success', 'Trial de ' . $trialDays . ' dias ativado! Aproveite o plano ' . $newPlan['name'] . '.');
        }

        return redirect()->to('/app/subscription')->with('success', 'Solicitação de upgrade enviada! Assim que o pagamento for confirmado pelo suporte, seu plano será ativado.');
    }
}
