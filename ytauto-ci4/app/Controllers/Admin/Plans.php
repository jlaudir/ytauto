<?php
// app/Controllers/Admin/Plans.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlanModel;

class Plans extends BaseController
{
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
    }

    public function index()
    {
        return view('admin/plans/index', [
            'title' => 'Planos — Admin',
            'plans' => $this->planModel->getStats(),
        ]);
    }

    public function create()
    {
        $db   = \Config\Database::connect();
        $perms = $db->from('permissions')->orderBy('group')->orderBy('label')->get()->getResultArray();
        return view('admin/plans/form', [
            'title'       => 'Novo Plano',
            'plan'        => null,
            'permissions' => $perms,
            'planPerms'   => [],
            'action'      => '/admin/plans/create',
        ]);
    }

    public function store()
    {
        $rules = [
            'name'          => 'required|min_length[2]|max_length[100]',
            'price_monthly' => 'required|decimal',
            'price_annual'  => 'required|decimal',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $slug   = url_title($this->request->getPost('name'), '-', true);
        $planId = $this->planModel->insert([
            'name'             => $this->request->getPost('name'),
            'slug'             => $slug,
            'description'      => $this->request->getPost('description'),
            'price_monthly'    => $this->request->getPost('price_monthly'),
            'price_annual'     => $this->request->getPost('price_annual'),
            'trial_days'       => (int)$this->request->getPost('trial_days'),
            'max_videos_month' => (int)$this->request->getPost('max_videos_month'),
            'max_voices'       => (int)$this->request->getPost('max_voices'),
            'has_admin_panel'  => $this->request->getPost('has_admin_panel') ? 1 : 0,
            'has_api_access'   => $this->request->getPost('has_api_access') ? 1 : 0,
            'has_analytics'    => $this->request->getPost('has_analytics') ? 1 : 0,
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0,
            'sort_order'       => (int)$this->request->getPost('sort_order'),
        ]);

        // Salva permissões
        $permIds = $this->request->getPost('permissions') ?? [];
        $this->planModel->syncPermissions($planId, $permIds);

        return redirect()->to('/admin/plans')->with('success', 'Plano criado com sucesso!');
    }

    public function edit(int $id)
    {
        $plan   = $this->planModel->getWithPermissions($id);
        $db     = \Config\Database::connect();
        $perms  = $db->from('permissions')->orderBy('group')->orderBy('label')->get()->getResultArray();
        return view('admin/plans/form', [
            'title'       => 'Editar Plano: ' . $plan['name'],
            'plan'        => $plan,
            'permissions' => $perms,
            'planPerms'   => $plan['permissions'] ?? [],
            'action'      => "/admin/plans/{$id}",
        ]);
    }

    public function update(int $id)
    {
        $this->planModel->update($id, [
            'name'             => $this->request->getPost('name'),
            'description'      => $this->request->getPost('description'),
            'price_monthly'    => $this->request->getPost('price_monthly'),
            'price_annual'     => $this->request->getPost('price_annual'),
            'trial_days'       => (int)$this->request->getPost('trial_days'),
            'max_videos_month' => (int)$this->request->getPost('max_videos_month'),
            'max_voices'       => (int)$this->request->getPost('max_voices'),
            'has_admin_panel'  => $this->request->getPost('has_admin_panel') ? 1 : 0,
            'has_api_access'   => $this->request->getPost('has_api_access') ? 1 : 0,
            'has_analytics'    => $this->request->getPost('has_analytics') ? 1 : 0,
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0,
            'sort_order'       => (int)$this->request->getPost('sort_order'),
        ]);

        $permIds = $this->request->getPost('permissions') ?? [];
        $this->planModel->syncPermissions($id, $permIds);

        return redirect()->to('/admin/plans')->with('success', 'Plano atualizado!');
    }

    public function delete(int $id)
    {
        // Não deleta se há usuários neste plano
        $count = db_connect()->from('users')->where('plan_id', $id)->countAllResults();
        if ($count > 0) {
            return $this->response->setJSON(['error' => "Existem {$count} usuário(s) neste plano."]);
        }
        $this->planModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    public function permissions()
    {
        $db    = \Config\Database::connect();
        $perms = $db->from('permissions')->orderBy('group')->orderBy('label')->get()->getResultArray();
        return view('admin/plans/permissions', [
            'title'       => 'Permissões do Sistema',
            'permissions' => $perms,
        ]);
    }

    public function savePermissions()
    {
        // Adicionar nova permissão ao sistema
        $db = \Config\Database::connect();
        $db->table('permissions')->insert([
            'key'   => $this->request->getPost('key'),
            'label' => $this->request->getPost('label'),
            'group' => $this->request->getPost('group'),
        ]);
        return redirect()->back()->with('success', 'Permissão criada!');
    }
}
