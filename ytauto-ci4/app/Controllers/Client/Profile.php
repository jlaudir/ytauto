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

        return view('client/profile', [
            'title' => 'Meu Perfil',
            'user'  => $user,
        ]);
    }

    public function update()
    {
        $userId = session()->get('user_id');
        $model  = new UserModel();

        $data = [
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
        $userId = session()->get('user_id');
        $sub    = (new SubscriptionModel())->getActiveSubscription($userId);
        $plan   = $sub ? (new PlanModel())->find($sub['plan_id']) : null;
        $payments = (new PaymentModel())->listWithDetails(['user_id' => $userId]);

        return view('client/subscription', [
            'title'        => 'Minha Assinatura',
            'subscription' => $sub,
            'plan'         => $plan,
            'payments'     => $payments,
        ]);
    }
}
