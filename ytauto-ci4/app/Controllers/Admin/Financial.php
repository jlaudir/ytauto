<?php
// app/Controllers/Admin/Financial.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use App\Models\UserModel;
use App\Models\PlanModel;

class Financial extends BaseController
{
    protected PaymentModel      $payModel;
    protected SubscriptionModel $subModel;

    public function __construct()
    {
        $this->payModel = new PaymentModel();
        $this->subModel = new SubscriptionModel();
    }

    public function index()
    {
        return view('admin/financial/index', [
            'title'           => 'Financeiro — Admin',
            'summary'         => $this->payModel->getSummary(),
            'monthly_revenue' => $this->payModel->getMonthlyRevenue(12),
            'overdue'         => $this->payModel->getOverduePayments(),
            'due_soon'        => $this->subModel->getDueThisPeriod(7),
            'recent_payments' => $this->payModel->listWithDetails(['status' => 'paid']),
        ]);
    }

    public function payments()
    {
        $filters = [
            'status' => $this->request->getGet('status'),
            'month'  => $this->request->getGet('month'),
        ];
        return view('admin/financial/payments', [
            'title'    => 'Pagamentos',
            'payments' => $this->payModel->listWithDetails($filters),
            'filters'  => $filters,
            'summary'  => $this->payModel->getSummary(),
        ]);
    }

    public function subscriptions()
    {
        $db   = \Config\Database::connect();
        $subs = $db->select('s.*, u.name as user_name, u.email, p.name as plan_name')
            ->from('subscriptions s')
            ->join('users u', 'u.id = s.user_id')
            ->join('plans p', 'p.id = s.plan_id')
            ->orderBy('s.expires_at', 'ASC')
            ->get()->getResultArray();

        return view('admin/financial/subscriptions', [
            'title'         => 'Assinaturas',
            'subscriptions' => $subs,
            'due_soon'      => $this->subModel->getDueThisPeriod(7),
            'overdue'       => $this->subModel->getOverdue(),
        ]);
    }

    public function markPaid(int $id)
    {
        $pay = $this->payModel->find($id);
        if (!$pay) return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);

        $this->payModel->update($id, [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'notes'   => ($pay['notes'] ? $pay['notes'] . ' | ' : '') . 'Marcado pago manualmente em ' . date('d/m/Y H:i'),
        ]);

        // Renova assinatura
        $sub = $this->subModel->find($pay['subscription_id']);
        if ($sub) {
            $newExpiry = date('Y-m-d', strtotime($sub['expires_at'] . ' +30 days'));
            $this->subModel->update($sub['id'], [
                'status'     => 'active',
                'expires_at' => $newExpiry,
            ]);

            // Cria próximo pagamento pendente
            $this->payModel->insert([
                'user_id'         => $pay['user_id'],
                'subscription_id' => $pay['subscription_id'],
                'amount'          => $pay['amount'],
                'method'          => $pay['method'],
                'status'          => 'pending',
                'due_date'        => $newExpiry,
            ]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Pagamento confirmado e assinatura renovada.']);
    }

    public function markFailed(int $id)
    {
        $this->payModel->update($id, ['status' => 'failed']);

        // Suspende assinatura
        $pay = $this->payModel->find($id);
        if ($pay) {
            $this->subModel->where('id', $pay['subscription_id'])->set(['status' => 'suspended'])->update();
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function createPayment()
    {
        $rules = [
            'user_id'         => 'required|is_not_unique[users.id]',
            'subscription_id' => 'required|is_not_unique[subscriptions.id]',
            'amount'          => 'required|decimal',
            'due_date'        => 'required|valid_date',
            'method'          => 'required',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $this->payModel->insert([
            'user_id'         => $this->request->getPost('user_id'),
            'subscription_id' => $this->request->getPost('subscription_id'),
            'amount'          => $this->request->getPost('amount'),
            'method'          => $this->request->getPost('method'),
            'status'          => 'pending',
            'due_date'        => $this->request->getPost('due_date'),
            'notes'           => $this->request->getPost('notes'),
        ]);

        return redirect()->back()->with('success', 'Cobrança gerada!');
    }

    public function overdue()
    {
        return view('admin/financial/overdue', [
            'title'   => 'Inadimplentes',
            'overdue' => $this->payModel->getOverduePayments(),
            'subs'    => $this->subModel->getOverdue(),
        ]);
    }

    public function report()
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $db   = \Config\Database::connect();

        $byMonth = $db->select('DATE_FORMAT(paid_at,"%m") as month, SUM(amount) as total, COUNT(*) as count')
            ->from('payments')
            ->where('status', 'paid')
            ->where('YEAR(paid_at)', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()->getResultArray();

        $byPlan = $db->select('p.name as plan_name, SUM(pay.amount) as total, COUNT(*) as count')
            ->from('payments pay')
            ->join('subscriptions s', 's.id = pay.subscription_id')
            ->join('plans p', 'p.id = s.plan_id')
            ->where('pay.status', 'paid')
            ->where('YEAR(pay.paid_at)', $year)
            ->groupBy('p.id')
            ->get()->getResultArray();

        return view('admin/financial/report', [
            'title'    => "Relatório Financeiro {$year}",
            'by_month' => $byMonth,
            'by_plan'  => $byPlan,
            'year'     => $year,
            'summary'  => $this->payModel->getSummary(),
        ]);
    }
}
