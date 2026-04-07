<?php
// app/Models/SubscriptionModel.php
namespace App\Models;
use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table         = 'subscriptions';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id','plan_id','billing_cycle','status','price_paid','started_at','expires_at','cancelled_at','notes'];
    protected $useTimestamps = true;

    public function getActiveSubscription(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->whereIn('status', ['active','trial'])
            ->orderBy('created_at', 'DESC')
            ->first() ?? [];
    }

    public function getOverdue(): array
    {
        $db = \Config\Database::connect();
        return $db->select('s.*, u.name as user_name, u.email, p.name as plan_name')
            ->from('subscriptions s')
            ->join('users u', 'u.id = s.user_id')
            ->join('plans p', 'p.id = s.plan_id')
            ->where('s.status', 'active')
            ->where('s.expires_at <', date('Y-m-d'))
            ->get()->getResultArray();
    }

    public function getDueThisPeriod(int $days = 7): array
    {
        $db = \Config\Database::connect();
        return $db->select('s.*, u.name as user_name, u.email, p.name as plan_name')
            ->from('subscriptions s')
            ->join('users u', 'u.id = s.user_id')
            ->join('plans p', 'p.id = s.plan_id')
            ->whereIn('s.status', ['active','trial'])
            ->where('s.expires_at <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('s.expires_at >=', date('Y-m-d'))
            ->orderBy('s.expires_at', 'ASC')
            ->get()->getResultArray();
    }
}
