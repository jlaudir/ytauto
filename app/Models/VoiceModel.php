<?php
// app/Models/VoiceModel.php
namespace App\Models;
use CodeIgniter\Model;

class VoiceModel extends Model
{
    protected $table         = 'voices';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['elevenlabs_id','name','gender','language','preview_url','is_active'];
    protected $useTimestamps = false;

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('gender')->orderBy('name')->findAll();
    }

    public function getMale(): array
    {
        return $this->where(['is_active' => 1, 'gender' => 'male'])->findAll();
    }

    public function getFemale(): array
    {
        return $this->where(['is_active' => 1, 'gender' => 'female'])->findAll();
    }
}
