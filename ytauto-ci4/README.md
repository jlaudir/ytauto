# YT.AUTO — Sistema de Automação YouTube com CodeIgniter 4

Sistema completo de geração de conteúdo YouTube com painel administrativo, controle financeiro, integração ElevenLabs e gestão de planos/assinaturas.

---

## 📋 Requisitos

- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- Apache com mod_rewrite ou Nginx
- Composer (para instalar CodeIgniter 4)
- Conta ElevenLabs (para narração com IA)

---

## 🚀 Instalação

### 1. Instalar CodeIgniter 4

```bash
composer create-project codeigniter4/appstarter ytauto-ci4
```

Copie todos os arquivos deste repositório para dentro do projeto CodeIgniter 4 criado, substituindo os existentes.

### 2. Banco de Dados

```bash
# Acesse seu MySQL e execute:
mysql -u root -p < database.sql
```

Isso cria o banco `ytauto` com todas as tabelas, dados iniciais, 3 planos padrão e o usuário admin.

**Credenciais admin padrão:**
- Email: `admin@ytauto.com`
- Senha: `Admin@123`

### 3. Configuração

```bash
# Copie o arquivo de configuração
cp env.example .env

# Edite .env com suas configurações
nano .env
```

Edite `app/Config/Database.php` com suas credenciais MySQL:

```php
'hostname' => 'localhost',
'username' => 'seu_usuario',
'password' => 'sua_senha',
'database' => 'ytauto',
```

Edite `app/Config/App.php`:
```php
public string $baseURL = 'http://seu-dominio.com/';
```

### 4. Permissões

```bash
chmod -R 775 writable/
```

### 5. Apache VirtualHost (recomendado)

```apache
<VirtualHost *:80>
    ServerName ytauto.local
    DocumentRoot /var/www/ytauto/public

    <Directory /var/www/ytauto/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## ⚙️ Configuração do ElevenLabs

1. Acesse [elevenlabs.io](https://elevenlabs.io) e crie uma conta
2. Copie sua API Key em **Profile → API Key**
3. No painel admin: **Configurações → ElevenLabs API Key**
4. Clique em **Vozes IA → Sincronizar com ElevenLabs** para importar as vozes

---

## 📁 Estrutura do Projeto

```
ytauto/
├── app/
│   ├── Config/
│   │   ├── App.php              # Configurações gerais
│   │   ├── Database.php         # Conexão MySQL
│   │   ├── Filters.php          # Registro dos filtros
│   │   └── Routes.php           # Todas as rotas
│   ├── Controllers/
│   │   ├── Auth.php             # Login, logout, registro
│   │   ├── Admin/
│   │   │   ├── Dashboard.php    # Dashboard admin
│   │   │   ├── Users.php        # CRUD clientes
│   │   │   ├── Plans.php        # CRUD planos + permissões
│   │   │   ├── Financial.php    # Controle financeiro completo
│   │   │   ├── Voices.php       # Gerenciar vozes ElevenLabs
│   │   │   ├── Videos.php       # Visualização de vídeos
│   │   │   └── Settings.php     # Configurações do sistema
│   │   ├── Client/
│   │   │   ├── Dashboard.php    # Dashboard do cliente
│   │   │   ├── VideoCreator.php # Criação de vídeos + narração
│   │   │   └── Profile.php      # Perfil e assinatura
│   │   └── Api/
│   │       └── NarrateController.php  # API JSON narração
│   ├── Filters/
│   │   ├── ClientFilter.php     # Proteção área do cliente
│   │   └── AdminFilter.php      # Proteção área admin
│   ├── Libraries/
│   │   └── ElevenLabs.php       # Integração API ElevenLabs
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── PlanModel.php
│   │   ├── SubscriptionModel.php
│   │   ├── PaymentModel.php
│   │   ├── VideoModel.php
│   │   └── VoiceModel.php
│   └── Views/
│       ├── layouts/
│       │   ├── admin.php        # Layout admin base
│       │   └── client.php       # Layout cliente base
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── admin/               # Todas as views admin
│       └── client/              # Todas as views cliente
├── public/
│   ├── css/
│   │   ├── admin.css
│   │   ├── app.css
│   │   └── auth.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── app.js
│   │   ├── creator.js           # Orquestrador de criação
│   │   ├── thumbnail.js         # Gerador de thumbnail Canvas
│   │   └── videoGen.js          # Animação de vídeo Canvas
│   └── .htaccess
├── writable/
│   └── uploads/
│       └── audio/               # Áudios gerados (ElevenLabs)
├── database.sql                 # Schema completo + dados iniciais
└── README.md
```

---

## 🗄️ Tabelas MySQL

| Tabela | Descrição |
|--------|-----------|
| `plans` | Planos com preços, limites e features |
| `permissions` | Permissões disponíveis no sistema |
| `plan_permissions` | Relação N:N planos ↔ permissões |
| `users` | Usuários (admins + clientes) |
| `subscriptions` | Assinaturas dos clientes |
| `payments` | Pagamentos e controle financeiro |
| `voices` | Vozes ElevenLabs sincronizadas |
| `videos` | Vídeos gerados por cada cliente |
| `activity_logs` | Log de ações dos usuários |
| `settings` | Configurações do sistema |

---

## 🎛️ Funcionalidades do Painel Admin

### Gestão de Clientes
- Criar, editar, ativar/desativar clientes
- Visualizar histórico completo (pagamentos, vídeos, logs)
- Gerar cobranças manuais
- Confirmar ou marcar falha em pagamentos

### Planos
- Criar e editar planos com preços mensais e anuais
- Definir limites (vídeos/mês, vozes, features)
- Associar permissões granulares por plano
- Períodos de trial configuráveis

### Controle Financeiro
- Dashboard com KPIs: faturado, pendente, inadimplente
- Gráfico de faturamento mensal (Chart.js)
- Lista de assinaturas vencendo em 7 dias
- Gestão de inadimplentes com 1 clique
- Relatório anual por mês e por plano
- Renovação automática ao confirmar pagamento

### Vozes ElevenLabs
- Sincronização automática com a API
- Ativar/desativar vozes individuais
- Preview de áudio direto no painel
- Monitoramento de caracteres usados

---

## 🎬 Fluxo do Cliente

1. **Cadastro** → Escolhe plano → Assinatura criada automaticamente
2. **Login** → Acesso restrito ao plano contratado
3. **Criar Vídeo** → Informa nicho → CI4 gera título, descrição, tags
4. **Thumbnail** → Gerada via Canvas API (1280×720)
5. **Narração** → Escolhe voz (masculina/feminina) → ElevenLabs gera MP3
6. **Salva** → Persiste no banco com thumbnail e caminho do áudio
7. **Histórico** → Lista todos os vídeos com player de áudio inline

---

## 🔐 Perfis de Acesso

| Permissão | Starter | Pro | Business |
|-----------|---------|-----|----------|
| Criar vídeos | ✓ | ✓ | ✓ |
| Histórico | ✓ | ✓ | ✓ |
| Escolher voz | ✓ | ✓ | ✓ |
| Narração ElevenLabs | ✗ | ✓ | ✓ |
| Baixar arquivos | ✗ | ✓ | ✓ |
| Analytics | ✗ | ✓ | ✓ |
| Acesso à API | ✗ | ✗ | ✓ |
| Postar YouTube | ✗ | ✓ | ✓ |

---

## 🔑 Credenciais Padrão

```
Admin:   admin@ytauto.com  /  Admin@123
```

**⚠️ Troque a senha do admin imediatamente após a instalação!**
