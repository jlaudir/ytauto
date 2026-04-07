<?php
// app/Models/VideoModel.php
namespace App\Models;
use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table         = 'videos';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id','niche','title','description','tags','hashtags','viral_score','duration_sec','voice_id','audio_path','thumbnail_data','youtube_id','youtube_url','status'];
    protected $useTimestamps = true;

    public function getUserVideos(int $userId, int $limit = 50): array
    {
        return $this->select('videos.*, v.name as voice_name, v.gender as voice_gender')
            ->join('voices v', 'v.id = videos.voice_id', 'left')
            ->where('videos.user_id', $userId)
            ->orderBy('videos.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function getMonthUsage(int $userId): int
    {
        return $this->where('user_id', $userId)
            ->where('DATE_FORMAT(created_at, "%Y-%m")', date('Y-m'))
            ->countAllResults();
    }

    public function adminList(): array
    {
        $db = \Config\Database::connect();
        return $db->table('videos')
            ->select('videos.id, videos.niche, videos.title, videos.status, videos.created_at, u.name as user_name, u.email, p.name as plan_name')            
            ->join('users u', 'u.id = videos.user_id')
            ->join('plans p', 'p.id = u.plan_id', 'left')
            ->orderBy('videos.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
