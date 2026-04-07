<?php
// app/Controllers/Admin/Voices.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VoiceModel;
use App\Libraries\MultiVozes;

class Voices extends BaseController
{
    public function index()
    {
        $voiceModel = new VoiceModel();
        $mv         = new MultiVozes();
        $connection = $mv->testConnection();

        return view('admin/voices/index', [
            'title'      => 'Vozes — MultiVozes Engine',
            'voices'     => $voiceModel->orderBy('gender')->orderBy('name')->findAll(),
            'connection' => $connection,
            'engine_url' => $mv->getBaseUrl(),
        ]);
    }

    public function sync()
    {
        $mv     = new MultiVozes();
        $result = $mv->listVoices();

        if (!$result['success']) {
            return $this->response->setJSON(['error' => $result['error']]);
        }

        $voiceModel = new VoiceModel();
        $synced     = 0;
        $updated    = 0;

        foreach ($result['voices'] as $v) {
            $voiceId = $v['voice_id'] ?? $v['id'] ?? '';
            $name    = $v['name']     ?? $voiceId;
            $gender  = $v['gender']   ?? 'neutral';

            if (empty($voiceId)) continue;

            $existing = $voiceModel->where('elevenlabs_id', $voiceId)->first();
            $row = [
                'elevenlabs_id' => $voiceId,
                'name'          => $name,
                'gender'        => in_array($gender, ['male', 'female']) ? $gender : 'neutral',
                'language'      => 'pt-BR',
                'preview_url'   => null,
                'is_active'     => 1,
            ];

            if ($existing) {
                $voiceModel->update($existing['id'], $row);
                $updated++;
            } else {
                $voiceModel->insert($row);
                $synced++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Concluído! {$synced} nova(s) voz(es) adicionada(s), {$updated} atualizada(s).",
        ]);
    }

    public function toggle(int $id)
    {
        $voiceModel = new VoiceModel();
        $voice      = $voiceModel->find($id);
        if (!$voice) {
            return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);
        }
        $voiceModel->update($id, ['is_active' => $voice['is_active'] ? 0 : 1]);
        return $this->response->setJSON(['success' => true, 'is_active' => !$voice['is_active']]);
    }
}
