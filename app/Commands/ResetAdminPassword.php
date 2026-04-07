<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

class ResetAdminPassword extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'admin:reset-password';
    protected $description = 'Reset admin password to default (admin123)';

    public function run(array $params)
    {
        $userModel = new UserModel();
        
        // Procura o primeiro admin
        $admin = $userModel->where('role', 'admin')->first();
        
        if ($admin) {
            $userModel->update($admin['id'], [
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT)
            ]);
            CLI::write('✓ Senha do admin resetada para: admin123', 'green');
            CLI::write("✓ Email: {$admin['email']}", 'green');
        } else {
            CLI::write('✗ Nenhum usuário admin encontrado!', 'red');
            
            // Cria um admin se não existir
            $userModel->insert([
                'name' => 'Administrador',
                'email' => 'admin@example.com',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'is_active' => 1
            ]);
            CLI::write('✓ Usuário admin criado com email: admin@example.com', 'green');
            CLI::write('✓ Senha: admin123', 'green');
        }
    }
}