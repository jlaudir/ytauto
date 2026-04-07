<?php
// app/Controllers/Admin/Users.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PlanModel;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;

class Users extends BaseController
{
    protected UserModel $userModel;
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->planModel = new PlanModel();
    }

    public function index()
    {
        $filters = [
            'search'  => $this->request->getGet('search'),
            'plan_id' => $this->request->getGet('plan_id'),
        ];
        return view('admin/users/index', [
            'title'   => 'Clientes — Admin',
            'users'   => $this->userModel->listWithPlan($filters),
            'plans'   => $this->planModel->where('is_active',1)->findAll(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return view('admin/users/form', [
            'title'  => 'Novo Cliente',
            'user'   => null,
            'plans'  => $this->planModel->where('is_active',1)->findAll(),
            'action' => '/admin/users/create',
        ]);
    }

    public function store()
    {
        $rules = [
            'name'    => 'required|min_length[3]',
            'email'   => 'required|valid_email|is_unique[users.email]',
            'password'=> 'required|min_length[8]',
            'plan_id' => 'required|is_not_unique[plans.id]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $planId = $this->request->getPost('plan_id');
        $plan   = $this->planModel->find($planId);

        $userId = $this->userModel->insert([
            'plan_id'       => $planId,
            'role'          => 'client',
            'name'          => $this->request->getPost('name'),
            'email'         => $this->request->getPost('email'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'phone'         => $this->request->getPost('phone'),
            'document'      => $this->request->getPost('document'),
            'is_active'     => 1,
            'email_verified'=> 1,
        ]);

        // Cria assinatura
        $subModel = new SubscriptionModel();
        $expires  = date('Y-m-d', strtotime('+30 days'));
        $subId    = $subModel->insert([
            'user_id'       => $userId,
            'plan_id'       => $planId,
            'billing_cycle' => $this->request->getPost('billing_cycle') ?? 'monthly',
            'status'        => 'active',
            'price_paid'    => $plan['price_monthly'],
            'started_at'    => date('Y-m-d'),
            'expires_at'    => $this->request->getPost('expires_at') ?? $expires,
        ]);

        return redirect()->to('/admin/users')->with('success', 'Cliente criado com sucesso!');
    }

    public function show(int $id)
    {
        $user = $this->userModel->getWithPlan($id);
        if (!$user) return redirect()->to('/admin/users')->with('error', 'Usuário não encontrado.');

        $db           = \Config\Database::connect();
        $subscriptions = $db->table('subscriptions s')->select('s.*, p.name as plan_name')            
            ->join('plans p', 'p.id = s.plan_id')
            ->where('s.user_id', $id)
            ->orderBy('s.created_at','DESC')
            ->get()->getResultArray();

        $payments = (new PaymentModel())->listWithDetails(['user_id' => $id]);
        $videos   = db_connect()->table('videos')->select('id,title,niche,status,created_at')->where('user_id',$id)->orderBy('created_at','DESC')->limit(20)->get()->getResultArray();
        $logs     = $db->table('activity_logs')->where('user_id',$id)->orderBy('created_at','DESC')->limit(20)->get()->getResultArray();

        return view('admin/users/show', [
            'title'         => 'Cliente: ' . $user['name'],
            'user'          => $user,
            'subscriptions' => $subscriptions,
            'payments'      => $payments,
            'videos'        => $videos,
            'logs'          => $logs,
            'plans'         => $this->planModel->findAll(),
        ]);
    }

    public function edit(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) return redirect()->to('/admin/users')->with('error', 'Usuário não encontrado.');
        return view('admin/users/form', [
            'title'  => 'Editar Cliente',
            'user'   => $user,
            'plans'  => $this->planModel->findAll(),
            'action' => "/admin/users/{$id}",
        ]);
    }

    public function update(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) return redirect()->to('/admin/users')->with('error', 'Usuário não encontrado.');

        $data = [
            'name'      => $this->request->getPost('name'),
            'email'     => $this->request->getPost('email'),
            'plan_id'   => $this->request->getPost('plan_id'),
            'phone'     => $this->request->getPost('phone'),
            'document'  => $this->request->getPost('document'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];

        $newPass = $this->request->getPost('password');
        if (!empty($newPass)) {
            $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
        }

        $this->userModel->update($id, $data);
        return redirect()->to("/admin/users/{$id}")->with('success', 'Cliente atualizado!');
    }

    public function toggle(int $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);
        $this->userModel->update($id, ['is_active' => $user['is_active'] ? 0 : 1]);
        return $this->response->setJSON(['success' => true, 'is_active' => !$user['is_active']]);
    }

    public function delete(int $id)
    {
        $this->userModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }
}
