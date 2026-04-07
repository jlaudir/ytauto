<?php
// app/Models/PlanModel.php
namespace App\Models;
use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table         = 'plans';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name','slug','description','price_monthly','price_annual','trial_days','max_videos_month','max_voices','has_admin_panel','has_api_access','has_analytics','features','is_active','sort_order'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithPermissions(int $id): array
    {
        $plan = $this->find($id);
        if (!$plan) return [];

        $db   = \Config\Database::connect();
        $perms = $db->select('permissions.*')
            ->from('plan_permissions pp')
            ->join('permissions', 'permissions.id = pp.permission_id')
            ->where('pp.plan_id', $id)
            ->get()->getResultArray();

        $plan['permissions'] = array_column($perms, 'key');
        return $plan;
    }

    public function syncPermissions(int $planId, array $permissionIds): void
    {
        $db = \Config\Database::connect();
        $db->table('plan_permissions')->where('plan_id', $planId)->delete();
        foreach ($permissionIds as $pid) {
            $db->table('plan_permissions')->insert(['plan_id' => $planId, 'permission_id' => (int)$pid]);
        }
    }

    public function getStats(): array
    {
        $db = \Config\Database::connect();
        return $db->select('p.id, p.name, p.price_monthly, COUNT(DISTINCT u.id) as user_count, COUNT(DISTINCT s.id) as active_subs')
            ->from('plans p')
            ->join('users u', 'u.plan_id = p.id', 'left')
            ->join('subscriptions s', 's.plan_id = p.id AND s.status IN ("active","trial")', 'left')
            ->groupBy('p.id')
            ->get()->getResultArray();
    }
}
