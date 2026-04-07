<?php
// app/Controllers/Admin/Dashboard.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use App\Models\VideoModel;
use App\Models\PlanModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $payModel  = new PaymentModel();
        $subModel  = new SubscriptionModel();
        $vidModel  = new VideoModel();
        $planModel = new PlanModel();

        $db = \Config\Database::connect();

        $data = [
            'title'          => 'Dashboard Admin — YT.AUTO',
            'total_clients'  => $userModel->where('role','client')->countAllResults(),
            'active_subs'    => $db->from('subscriptions')->whereIn('status',['active','trial'])->countAllResults(),
            'total_videos'   => $db->from('videos')->countAllResults(),
            'summary'        => $payModel->getSummary(),
            'plan_stats'     => $planModel->getStats(),
            'recent_users'   => $userModel->listWithPlan(['search' => '']),
            'overdue_payments' => $payModel->getOverduePayments(),
            'due_soon'       => $subModel->getDueThisPeriod(7),
            'monthly_revenue'=> $payModel->getMonthlyRevenue(6),
            'recent_videos'  => array_slice($vidModel->adminList(), 0, 10),
        ];

        return view('admin/dashboard', $data);
    }
}
