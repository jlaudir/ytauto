<?php
// app/Controllers/Client/Dashboard.php
namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\VideoModel;
use App\Models\PlanModel;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userId    = session()->get('user_id');
        $vidModel  = new VideoModel();
        $subModel  = new SubscriptionModel();
        $planModel = new PlanModel();

        $plan = $planModel->find(session()->get('plan_id'));
        $sub  = $subModel->getActiveSubscription($userId);

        // Próximo pagamento
        $nextPayment = db_connect()
            ->from('payments')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('due_date','ASC')
            ->get()->getRowArray();

        $monthUsage = $vidModel->getMonthUsage($userId);
        $maxVideos  = (int)($plan['max_videos_month'] ?? 10);

        return view('client/dashboard', [
            'title'        => 'Dashboard — YT.AUTO',
            'plan'         => $plan,
            'subscription' => $sub,
            'next_payment' => $nextPayment,
            'month_usage'  => $monthUsage,
            'max_videos'   => $maxVideos,
            'recent_videos'=> $vidModel->getUserVideos($userId, 6),
        ]);
    }
}
