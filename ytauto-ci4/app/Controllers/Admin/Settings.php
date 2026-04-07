<?php
// app/Controllers/Admin/Settings.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Settings extends BaseController
{
    public function index()
    {
        $db       = \Config\Database::connect();
        $settings = $db->from('settings')->orderBy('group')->orderBy('key')->get()->getResultArray();

        // Indexa por key
        $indexed = [];
        foreach ($settings as $s) $indexed[$s['key']] = $s['value'];

        return view('admin/settings/index', [
            'title'    => 'Configurações do Sistema',
            'settings' => $indexed,
            'raw'      => $settings,
        ]);
    }

    public function save()
    {
        $db   = \Config\Database::connect();
        $post = $this->request->getPost();
        unset($post[$this->security->getTokenName()]);

        foreach ($post as $key => $value) {
            // Não salva campo vazio de senha (mantém existente)
            if (in_array($key, ['elevenlabs_api_key','youtube_api_key','smtp_pass']) && $value === '') continue;

            $exists = $db->from('settings')->where('key', $key)->countAllResults();
            if ($exists) {
                $db->table('settings')->where('key', $key)->update(['value' => $value]);
            } else {
                $db->table('settings')->insert(['key' => $key, 'value' => $value, 'group' => 'custom']);
            }
        }

        return redirect()->back()->with('success', 'Configurações salvas com sucesso!');
    }
}
