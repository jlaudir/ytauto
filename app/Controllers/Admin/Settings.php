<?php
// app/Controllers/Admin/Settings.php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Settings extends BaseController
{
    public function index()
    {
        $db       = \Config\Database::connect();
        $settings = $db->table('settings')->orderBy('group')->orderBy('key')->get()->getResultArray();

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
        // unset($post[$this->security->getTokenName()]);
        unset($post[csrf_token()]);

        foreach ($post as $key => $value) {
            if (in_array($key, ['elevenlabs_api_key','youtube_api_key','smtp_pass','asaas_api_key','multivozes_api_key']) && $value === '') continue;
            $exists = $db->table('settings')->where('key', $key)->countAllResults();
            if ($exists) {
                $db->table('settings')->where('key', $key)->update(['value' => $value]);
            } else {
                $db->table('settings')->insert(['key' => $key, 'value' => $value, 'group' => 'custom']);
            }
        }

        return redirect()->back()->with('success', 'Configurações salvas com sucesso!');
    }

    public function testAsaas()
    {
        $asaas  = new \App\Libraries\Asaas();
        $result = $asaas->testConnection();
        return $this->response->setJSON($result);
    }
}
