<?php
// app/Controllers/Api/NarrateController.php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\MultiVozes;
use App\Models\VoiceModel;

class NarrateController extends BaseController
{
    public function generate()
    {
        $text    = $this->request->getPost('text');
        $voiceId = $this->request->getPost('voice_id'); // voice ID do Edge TTS, ex: pt-BR-AntonioNeural
        $videoId = (int)$this->request->getPost('video_id');

        if (!$text || !$voiceId) {
            return $this->response->setJSON(['error' => 'text e voice_id são obrigatórios'])->setStatusCode(400);
        }

        // Verifica permissão de narração do plano
        $planId = session()->get('plan_id');
        if ($planId) {
            $db = \Config\Database::connect();
            $hasNarrate = $db->table('plan_permissions pp')
                ->join('permissions p', 'p.id = pp.permission_id')
                ->where('pp.plan_id', $planId)
                ->where('p.key', 'videos.narrate')
                ->countAllResults() > 0;

            if (!$hasNarrate) {
                return $this->response->setJSON([
                    'error' => 'Seu plano não inclui narração com IA. Faça upgrade para o plano Pro.'
                ])->setStatusCode(403);
            }
        }

        // Busca a voz no banco pelo voice_id (coluna elevenlabs_id reutilizada)
        $voiceModel = new VoiceModel();
        $voice      = $voiceModel->where('elevenlabs_id', $voiceId)->first();

        if (!$voice || !$voice['is_active']) {
            return $this->response->setJSON(['error' => 'Voz não disponível ou desativada.'])->setStatusCode(400);
        }

        // Gera o áudio via MultiVozes Engine
        $mv     = new MultiVozes();
        $result = $mv->textToSpeech($text, $voiceId, $videoId ?: 0);

        if (!$result['success']) {
            return $this->response->setJSON(['error' => $result['error']])->setStatusCode(500);
        }

        // Atualiza o vídeo com o caminho do áudio gerado
        if ($videoId) {
            db_connect()->table('videos')
                ->where('id', $videoId)
                ->update([
                    'voice_id'   => $voice['id'],
                    'audio_path' => $result['rel_path'],
                    'status'     => 'ready',
                ]);
        }

        return $this->response->setJSON([
            'success'      => true,
            'audio_url'    => base_url('writable/' . $result['rel_path']),
            'filename'     => $result['filename'],
            'voice_name'   => $voice['name'],
            'voice_gender' => $voice['gender'],
            'engine'       => 'MultiVozes BR Engine',
        ]);
    }

    public function voices()
    {
        $voiceModel = new VoiceModel();
        return $this->response->setJSON([
            'male'   => $voiceModel->getMale(),
            'female' => $voiceModel->getFemale(),
        ]);
    }
}
