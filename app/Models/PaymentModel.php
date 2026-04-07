<?php
// app/Models/PaymentModel.php
namespace App\Models;
use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id','subscription_id','amount','method','status','due_date','paid_at','reference','notes'];
    protected $useTimestamps = true;

    public function listWithDetails(array $filters = []): array
    {
        $q = $this->select('payments.*, u.name as user_name, u.email, p.name as plan_name')
            ->join('users u', 'u.id = payments.user_id')
            ->join('subscriptions s', 's.id = payments.subscription_id')
            ->join('plans p', 'p.id = s.plan_id');

        if (!empty($filters['status']))  $q->where('payments.status', $filters['status']);
        if (!empty($filters['user_id'])) $q->where('payments.user_id', $filters['user_id']);
        if (!empty($filters['month']))   $q->where('DATE_FORMAT(payments.due_date, "%Y-%m")', $filters['month']);

        return $q->orderBy('payments.due_date', 'DESC')->findAll();
    }

    public function getMonthlyRevenue(int $months = 12): array
    {
        $db          = \Config\Database::connect();
        $monthsInt   = (int) $months;
        $dateFrom    = date('Y-m-d', strtotime('-' . $monthsInt . ' months'));

        return $db->table('payments')
            ->select('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as total, COUNT(*) as count')            
            ->where('status', 'paid')
            ->where('paid_at >=', $dateFrom)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get()->getResultArray();
    }

    public function getSummary(): array
    {
        $db = \Config\Database::connect();
        $currentMonth = date('Y-m');

        $paid     = $db->table('payments')->selectSum('amount')->where('status','paid')->where('DATE_FORMAT(paid_at,"%Y-%m")', $currentMonth)->get()->getRow();
        $pending  = $db->table('payments')->selectSum('amount')->where('status','pending')->get()->getRow();
        $overdue  = $db->table('payments')->selectSum('amount')->where('status','pending')->where('due_date <', date('Y-m-d'))->get()->getRow();

        return [
            'paid_this_month' => (float)($paid->amount    ?? 0),
            'pending_total'   => (float)($pending->amount ?? 0),
            'overdue_total'   => (float)($overdue->amount ?? 0),
        ];
    }

    public function getOverduePayments(): array
    {
        $db = \Config\Database::connect();
        return $db->table('payments')->select('payments.*, u.name as user_name, u.email, p.name as plan_name')            
            ->join('users u', 'u.id = payments.user_id')
            ->join('subscriptions s', 's.id = payments.subscription_id')
            ->join('plans p', 'p.id = s.plan_id')
            ->where('payments.status', 'pending')
            ->where('payments.due_date <', date('Y-m-d'))
            ->orderBy('payments.due_date', 'ASC')
            ->get()->getResultArray();
    }
}
