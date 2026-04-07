<?php
// app/Controllers/Client/VideoCreator.php
namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\VideoModel;
use App\Models\VoiceModel;
use App\Models\PlanModel;
use App\Libraries\ElevenLabs;

class VideoCreator extends BaseController
{
    protected VideoModel $vidModel;
    protected VoiceModel $voiceModel;

    public function __construct()
    {
        $this->vidModel   = new VideoModel();
        $this->voiceModel = new VoiceModel();
    }

    public function index()
    {
        $userId   = session()->get('user_id');
        $planId   = session()->get('plan_id');
        $plan     = (new PlanModel())->find($planId);
        $usage    = $this->vidModel->getMonthUsage($userId);
        $maxVid   = (int)($plan['max_videos_month'] ?? 10);

        // Verifica limite
        if ($maxVid > 0 && $usage >= $maxVid) {
            return redirect()->to('/app/dashboard')->with('error', "Você atingiu o limite de {$maxVid} vídeos este mês. Faça upgrade do plano.");
        }

        return view('client/creator', [
            'title'        => 'Criar Vídeo — YT.AUTO',
            'voices_male'  => $this->voiceModel->getMale(),
            'voices_female'=> $this->voiceModel->getFemale(),
            'plan'         => $plan,
            'usage'        => $usage,
            'max_videos'   => $maxVid,
        ]);
    }

    /** AJAX: Gera título, descrição, tags (retorna JSON) */
    public function generate()
    {
        $niche = $this->request->getPost('niche');
        if (!$niche) return $this->response->setJSON(['error' => 'Nicho não informado'])->setStatusCode(400);

        // Usa o gerador JS-side mas também disponibiliza versão PHP
        $title       = $this->generateTitle($niche);
        $description = $this->generateDescription($niche, $title);
        $tags        = $this->generateTags($niche);
        $hashtags    = $this->generateHashtags($niche);
        $viralScore  = $this->calcViralScore($title);

        return $this->response->setJSON([
            'success'     => true,
            'title'       => $title,
            'description' => $description,
            'tags'        => $tags,
            'hashtags'    => $hashtags,
            'viral_score' => $viralScore,
        ]);
    }

    /** AJAX: Chama ElevenLabs e gera áudio */
    public function narrate()
    {
        $userId  = session()->get('user_id');
        $planId  = session()->get('plan_id');
        $plan    = (new PlanModel())->find($planId);

        // Verifica permissão de narração
        $hasNarrate = $this->checkPermission('videos.narrate');
        if (!$hasNarrate) {
            return $this->response->setJSON(['error' => 'Seu plano não inclui narração com IA. Faça upgrade!'])->setStatusCode(403);
        }

        $text    = $this->request->getPost('text');
        $voiceId = $this->request->getPost('voice_id'); // elevenlabs_id
        $videoId = (int)$this->request->getPost('video_id');

        if (!$text || !$voiceId) {
            return $this->response->setJSON(['error' => 'Texto e voz são obrigatórios'])->setStatusCode(400);
        }

        // Busca voz no DB
        $voice = $this->voiceModel->where('elevenlabs_id', $voiceId)->first();
        if (!$voice || !$voice['is_active']) {
            return $this->response->setJSON(['error' => 'Voz não disponível'])->setStatusCode(400);
        }

        $el     = new ElevenLabs();
        $result = $el->textToSpeech($text, $voiceId, $videoId ?: time());

        if (!$result['success']) {
            return $this->response->setJSON(['error' => $result['error']])->setStatusCode(500);
        }

        // Atualiza vídeo com caminho do áudio
        if ($videoId) {
            $this->vidModel->update($videoId, [
                'voice_id'   => $voice['id'],
                'audio_path' => $result['rel_path'],
                'status'     => 'ready',
            ]);
        }

        return $this->response->setJSON([
            'success'    => true,
            'audio_url'  => base_url('writable/' . $result['rel_path']),
            'filename'   => $result['filename'],
            'voice_name' => $voice['name'],
            'voice_gender'=> $voice['gender'],
        ]);
    }

    /** Salva vídeo gerado no banco */
    public function save()
    {
        $userId = session()->get('user_id');

        $videoId = $this->vidModel->insert([
            'user_id'        => $userId,
            'niche'          => $this->request->getPost('niche'),
            'title'          => $this->request->getPost('title'),
            'description'    => $this->request->getPost('description'),
            'tags'           => $this->request->getPost('tags'),
            'hashtags'       => $this->request->getPost('hashtags'),
            'viral_score'    => (int)$this->request->getPost('viral_score'),
            'duration_sec'   => (int)$this->request->getPost('duration_sec'),
            'thumbnail_data' => $this->request->getPost('thumbnail_data'),
            'status'         => 'ready',
        ]);

        return $this->response->setJSON(['success' => true, 'video_id' => $videoId]);
    }

    public function history()
    {
        $userId = session()->get('user_id');
        return view('client/history', [
            'title'  => 'Histórico de Vídeos',
            'videos' => $this->vidModel->getUserVideos($userId),
        ]);
    }

    public function show(int $id)
    {
        $userId = session()->get('user_id');
        $video  = $this->vidModel->where(['id' => $id, 'user_id' => $userId])->first();
        if (!$video) return redirect()->to('/app/history')->with('error', 'Vídeo não encontrado.');
        return view('client/video_show', ['title' => $video['title'], 'video' => $video]);
    }

    public function delete(int $id)
    {
        $userId = session()->get('user_id');
        $video  = $this->vidModel->where(['id' => $id, 'user_id' => $userId])->first();
        if (!$video) return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);
        $this->vidModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    public function voices()
    {
        return $this->response->setJSON([
            'male'   => $this->voiceModel->getMale(),
            'female' => $this->voiceModel->getFemale(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────

    protected function checkPermission(string $key): bool
    {
        $planId = session()->get('plan_id');
        if (!$planId) return false;
        $db = \Config\Database::connect();
        return $db->from('plan_permissions pp')
            ->join('permissions p', 'p.id = pp.permission_id')
            ->where('pp.plan_id', $planId)
            ->where('p.key', $key)
            ->countAllResults() > 0;
    }

    protected function generateTitle(string $niche): string
    {
        $templates = [
            "%s: TUDO que você precisa saber (Guia Definitivo 2025)",
            "%d SEGREDOS sobre %s que ninguém te contou",
            "Por que 99%% falha em %s — e como você pode ser o 1%%",
            "PARE de usar %s ERRADO! Faça ISSO agora",
            "A VERDADE sobre %s que os especialistas escondem",
            "Aprendi %s em 1 semana e isso aconteceu (chocante)",
            "Como ganhar com %s em 30 dias (método validado)",
            "%s para iniciantes: o guia que eu queria ter tido",
        ];
        $tpl = $templates[array_rand($templates)];
        if (strpos($tpl, '%d') !== false) {
            return sprintf($tpl, rand(5, 12), ucwords($niche));
        }
        return sprintf($tpl, ucwords($niche));
    }

    protected function generateDescription(string $niche, string $title): string
    {
        $niche = ucwords($niche);
        return "Neste vídeo exclusivo sobre {$niche}, vou revelar tudo que você precisa saber para obter resultados reais — sem enrolação.\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━\n📌 TIMESTAMPS:\n━━━━━━━━━━━━━━━━━━━━━━\n"
            . "00:00 — Introdução\n00:30 — O problema que ninguém fala\n01:00 — A solução revelada\n01:30 — Como aplicar agora\n02:00 — Resultados e conclusão\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━\n🔗 LINKS MENCIONADOS:\n━━━━━━━━━━━━━━━━━━━━━━\n"
            . "✅ Produto recomendado → https://link.afiliado.com\n📚 Curso completo → https://curso.link.com\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━\n🚀 ENGAJE COM O CANAL:\n━━━━━━━━━━━━━━━━━━━━━━\n"
            . "👍 Deixa o LIKE se o vídeo ajudou!\n🔔 ATIVA O SININHO para não perder a PARTE 2!\n📺 SE INSCREVE — novo conteúdo toda semana!\n"
            . "💬 Comenta aqui: qual parte foi mais útil?\n\n"
            . "#" . strtolower(str_replace(' ', '', $niche)) . " #dicas #tutorial #brasil #2025";
    }

    protected function generateTags(string $niche): string
    {
        $n    = strtolower($niche);
        $tags = [$niche, "$niche tutorial", "$niche dicas", "$niche 2025", "$niche para iniciantes",
                 "como fazer $niche", "aprender $niche", "melhor $niche", "$niche passo a passo", "$niche completo"];
        return implode(', ', $tags);
    }

    protected function generateHashtags(string $niche): string
    {
        $slug = '#' . strtolower(str_replace([' ', '-'], '', $niche));
        return "$slug #tutorial #dicas #brasil #youtube #viral #aprenda #2025 #conteudo #conhecimento";
    }

    protected function calcViralScore(string $title): int
    {
        $score = 45;
        if (preg_match('/\d+/', $title)) $score += 12;
        foreach (['SEGREDO','VERDADE','PARE','CHOCANTE','ERRADO','NUNCA','SEMPRE'] as $w) {
            if (stripos($title, $w) !== false) $score += 7;
        }
        $len = strlen($title);
        if ($len >= 50 && $len <= 70) $score += 10;
        $score += rand(-3, 5);
        return min(99, max(60, $score));
    }
}
