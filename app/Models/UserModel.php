<?php
// app/Models/UserModel.php
namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['plan_id','role','name','email','password_hash','avatar','phone','document','is_active','email_verified','remember_token','last_login_at'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $hidden           = ['password_hash'];

    public function getWithPlan(int $id): array
    {
        return $this->select('users.*, plans.name as plan_name, plans.slug as plan_slug')
            ->join('plans', 'plans.id = users.plan_id', 'left')
            ->where('users.id', $id)
            ->first() ?? [];
    }

    public function listWithPlan(array $filters = []): array
    {
        $q = $this->select('users.*, plans.name as plan_name, s.status as sub_status, s.expires_at')
            ->join('plans', 'plans.id = users.plan_id', 'left')
            ->join('subscriptions s', 's.user_id = users.id AND s.status IN ("active","trial","suspended")', 'left')
            ->where('users.role', 'client');

        if (!empty($filters['search'])) {
            $q->groupStart()
              ->like('users.name', $filters['search'])
              ->orLike('users.email', $filters['search'])
              ->groupEnd();
        }
        if (!empty($filters['plan_id'])) $q->where('users.plan_id', $filters['plan_id']);
        if (isset($filters['is_active'])) $q->where('users.is_active', $filters['is_active']);

        return $q->orderBy('users.created_at', 'DESC')->findAll();
    }
}
