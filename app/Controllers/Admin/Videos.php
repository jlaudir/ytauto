<?php
// app/Controllers/Admin/Videos.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VideoModel;

class Videos extends BaseController
{
    public function index()
    {
        return view('admin/videos/index', [
            'title'  => 'Vídeos Gerados',
            'videos' => (new VideoModel())->adminList(),
        ]);
    }

    public function show(int $id)
    {
        $db    = \Config\Database::connect();
        $video = $db->table('videos')->select('videos.*, u.name as user_name, u.email, v.name as voice_name, v.gender as voice_gender')            
            ->join('users u', 'u.id = videos.user_id')
            ->join('voices v', 'v.id = videos.voice_id', 'left')
            ->where('videos.id', $id)
            ->get()->getRowArray();

        if (!$video) return redirect()->to('/admin/videos')->with('error', 'Vídeo não encontrado.');

        return view('admin/videos/show', [
            'title' => 'Vídeo #' . $id,
            'video' => $video,
        ]);
    }
}
