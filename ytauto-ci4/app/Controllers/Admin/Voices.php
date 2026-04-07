<?php
// app/Controllers/Admin/Voices.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VoiceModel;
use App\Libraries\ElevenLabs;

class Voices extends BaseController
{
    public function index()
    {
        $voiceModel = new VoiceModel();
        $el         = new ElevenLabs();
        $info       = $el->getSubscriptionInfo();

        return view('admin/voices/index', [
            'title'  => 'Vozes ElevenLabs',
            'voices' => $voiceModel->orderBy('gender')->orderBy('name')->findAll(),
            'el_info'=> $info,
        ]);
    }

    public function sync()
    {
        $el     = new ElevenLabs();
        $result = $el->listVoices();

        if (!$result['success']) {
            return $this->response->setJSON(['error' => $result['error']]);
        }

        $voiceModel = new VoiceModel();
        $synced     = 0;

        foreach ($result['voices'] as $v) {
            $existing = $voiceModel->where('elevenlabs_id', $v['voice_id'])->first();
            $gender   = 'neutral';
            foreach ($v['labels'] ?? [] as $k => $val) {
                if ($k === 'gender') { $gender = strtolower($val); break; }
            }

            $row = [
                'elevenlabs_id' => $v['voice_id'],
                'name'          => $v['name'],
                'gender'        => in_array($gender, ['male','female']) ? $gender : 'neutral',
                'language'      => 'pt',
                'preview_url'   => $v['preview_url'] ?? null,
                'is_active'     => 1,
            ];

            if ($existing) {
                $voiceModel->update($existing['id'], $row);
            } else {
                $voiceModel->insert($row);
                $synced++;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Sincronização concluída. {$synced} novas vozes adicionadas.",
        ]);
    }

    public function toggle(int $id)
    {
        $voiceModel = new VoiceModel();
        $voice      = $voiceModel->find($id);
        if (!$voice) return $this->response->setJSON(['error' => 'Não encontrado'])->setStatusCode(404);
        $voiceModel->update($id, ['is_active' => $voice['is_active'] ? 0 : 1]);
        return $this->response->setJSON(['success' => true, 'is_active' => !$voice['is_active']]);
    }
}
