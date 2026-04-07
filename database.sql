-- ============================================================
-- YT.AUTO — Schema MySQL Completo
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE DATABASE IF NOT EXISTS `ytauto` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ytauto`;

-- ----------------------------------------------------------------
-- PLANOS
-- ----------------------------------------------------------------
CREATE TABLE `plans` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`              VARCHAR(100) NOT NULL,
  `slug`              VARCHAR(100) NOT NULL UNIQUE,
  `description`       TEXT,
  `price_monthly`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_annual`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `trial_days`        INT UNSIGNED NOT NULL DEFAULT 0,
  `max_videos_month`  INT UNSIGNED NOT NULL DEFAULT 10   COMMENT '0 = ilimitado',
  `max_voices`        INT UNSIGNED NOT NULL DEFAULT 2,
  `has_admin_panel`   TINYINT(1) NOT NULL DEFAULT 0,
  `has_api_access`    TINYINT(1) NOT NULL DEFAULT 0,
  `has_analytics`     TINYINT(1) NOT NULL DEFAULT 0,
  `features`          JSON          COMMENT 'Lista extra de features em JSON',
  `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`        INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- USUÁRIOS (Clientes + Admins)
-- ----------------------------------------------------------------
CREATE TABLE `users` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `plan_id`        INT UNSIGNED NULL,
  `role`           ENUM('admin','client') NOT NULL DEFAULT 'client',
  `name`           VARCHAR(150) NOT NULL,
  `email`          VARCHAR(180) NOT NULL UNIQUE,
  `password_hash`  VARCHAR(255) NOT NULL,
  `avatar`         VARCHAR(255) NULL,
  `phone`          VARCHAR(30) NULL,
  `document`       VARCHAR(30) NULL COMMENT 'CPF/CNPJ',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `remember_token` VARCHAR(64) NULL,
  `last_login_at`  DATETIME NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- ASSINATURAS
-- ----------------------------------------------------------------
CREATE TABLE `subscriptions` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `plan_id`        INT UNSIGNED NOT NULL,
  `billing_cycle`  ENUM('monthly','annual') NOT NULL DEFAULT 'monthly',
  `status`         ENUM('trial','active','suspended','cancelled','expired') NOT NULL DEFAULT 'active',
  `price_paid`     DECIMAL(10,2) NOT NULL,
  `started_at`     DATE NOT NULL,
  `expires_at`     DATE NOT NULL,
  `cancelled_at`   DATETIME NULL,
  `notes`          TEXT NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- PAGAMENTOS / CONTROLE FINANCEIRO
-- ----------------------------------------------------------------
CREATE TABLE `payments` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `subscription_id` INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(10,2) NOT NULL,
  `method`         ENUM('pix','boleto','credit_card','manual','trial') NOT NULL DEFAULT 'manual',
  `status`         ENUM('pending','paid','failed','refunded','chargeback') NOT NULL DEFAULT 'pending',
  `due_date`       DATE NOT NULL,
  `paid_at`        DATETIME NULL,
  `reference`      VARCHAR(100) NULL COMMENT 'ID externo (gateway, etc)',
  `notes`          TEXT NULL,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_sub`  FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- PERFIS DE ACESSO / PERMISSÕES
-- ----------------------------------------------------------------
CREATE TABLE `permissions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`         VARCHAR(100) NOT NULL UNIQUE  COMMENT 'ex: videos.create, admin.users',
  `label`       VARCHAR(150) NOT NULL,
  `group`       VARCHAR(80) NOT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `plan_permissions` (
  `plan_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`plan_id`, `permission_id`),
  CONSTRAINT `fk_pp_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- VOZES ELEVENLABS (catálogo sincronizável)
-- ----------------------------------------------------------------
CREATE TABLE `voices` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `elevenlabs_id` VARCHAR(100) NOT NULL UNIQUE,
  `name`         VARCHAR(150) NOT NULL,
  `gender`       ENUM('male','female','neutral') NOT NULL DEFAULT 'neutral',
  `language`     VARCHAR(10) NOT NULL DEFAULT 'pt',
  `preview_url`  VARCHAR(500) NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- VÍDEOS GERADOS
-- ----------------------------------------------------------------
CREATE TABLE `videos` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED NOT NULL,
  `niche`         VARCHAR(200) NOT NULL,
  `title`         VARCHAR(300) NOT NULL,
  `description`   LONGTEXT NULL,
  `tags`          TEXT NULL,
  `hashtags`      TEXT NULL,
  `viral_score`   TINYINT UNSIGNED NULL,
  `duration_sec`  SMALLINT UNSIGNED NULL,
  `voice_id`      INT UNSIGNED NULL,
  `audio_path`    VARCHAR(500) NULL,
  `thumbnail_data` LONGTEXT NULL COMMENT 'base64 da thumbnail',
  `youtube_id`    VARCHAR(30) NULL,
  `youtube_url`   VARCHAR(300) NULL,
  `status`        ENUM('draft','processing','ready','posted','failed') NOT NULL DEFAULT 'draft',
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_vid_user`  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vid_voice` FOREIGN KEY (`voice_id`) REFERENCES `voices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- LOG DE ATIVIDADES
-- ----------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NULL,
  `action`     VARCHAR(150) NOT NULL,
  `detail`     TEXT NULL,
  `ip`         VARCHAR(45) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- CONFIGURAÇÕES DO SISTEMA
-- ----------------------------------------------------------------
CREATE TABLE `settings` (
  `key`        VARCHAR(100) PRIMARY KEY,
  `value`      TEXT NULL,
  `label`      VARCHAR(200) NULL,
  `group`      VARCHAR(80) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Permissões
INSERT INTO `permissions` (`key`, `label`, `group`) VALUES
('videos.create',       'Criar vídeos',                 'Vídeos'),
('videos.narrate',      'Gerar narração ElevenLabs',    'Vídeos'),
('videos.download',     'Baixar vídeos e thumbnails',   'Vídeos'),
('videos.post_youtube', 'Postar no YouTube',            'Vídeos'),
('videos.history',      'Acessar histórico de vídeos',  'Vídeos'),
('analytics.view',      'Ver analytics de canal',       'Analytics'),
('voices.choose',       'Escolher voz de narração',     'Narração'),
('api.access',          'Acesso à API REST',            'API');

-- Planos padrão (Free é o ID 1 e vinculado automaticamente no cadastro)
INSERT INTO `plans` (`name`,`slug`,`description`,`price_monthly`,`price_annual`,`trial_days`,`max_videos_month`,`max_voices`,`has_analytics`,`has_api_access`,`is_active`,`sort_order`) VALUES
('Free',     'free',     'Comece gratuitamente, sem cartão',  0.00,    0.00,   0,  3,  1, 0, 0, 1, 0),
('Starter',  'starter',  'Ideal para quem está começando',   29.90,  299.00,  0,  15, 2, 0, 0, 1, 1),
('Pro',      'pro',      'Para criadores profissionais',     79.90,  799.00,  7,  50, 4, 1, 0, 1, 2),
('Business', 'business', 'Uso ilimitado para agências',     199.90, 1999.00, 7,  0,  8, 1, 1, 1, 3);

-- Permissões por plano (Free — ID 1)
INSERT INTO `plan_permissions` (`plan_id`,`permission_id`)
SELECT 1, id FROM permissions WHERE `key` IN ('videos.create','videos.history');

-- Permissões por plano (Starter — ID 2)
INSERT INTO `plan_permissions` (`plan_id`,`permission_id`)
SELECT 2, id FROM permissions WHERE `key` IN ('videos.create','videos.history','voices.choose','videos.download');

-- Permissões por plano (Pro — ID 3)
INSERT INTO `plan_permissions` (`plan_id`,`permission_id`)
SELECT 3, id FROM permissions WHERE `key` IN ('videos.create','videos.narrate','videos.download','videos.history','voices.choose','analytics.view','videos.post_youtube');

-- Permissões por plano (Business — ID 4)
INSERT INTO `plan_permissions` (`plan_id`,`permission_id`)
SELECT 4, id FROM permissions;

-- Vozes PT-BR do Microsoft Edge Neural (via MultiVozes BR Engine)
INSERT INTO `voices` (`elevenlabs_id`,`name`,`gender`,`language`,`preview_url`,`is_active`) VALUES
-- Masculinas
('pt-BR-AntonioNeural',   'Antônio',   'male',   'pt-BR', NULL, 1),
('pt-BR-FabioNeural',     'Fábio',     'male',   'pt-BR', NULL, 1),
('pt-BR-HumbertoNeural',  'Humberto',  'male',   'pt-BR', NULL, 1),
('pt-BR-JulioNeural',     'Júlio',     'male',   'pt-BR', NULL, 1),
('pt-BR-NicolauNeural',   'Nicolau',   'male',   'pt-BR', NULL, 1),
('pt-BR-ValerioNeural',   'Valério',   'male',   'pt-BR', NULL, 1),
-- Femininas
('pt-BR-FranciscaNeural', 'Francisca', 'female', 'pt-BR', NULL, 1),
('pt-BR-BrendaNeural',    'Brenda',    'female', 'pt-BR', NULL, 1),
('pt-BR-ElzaNeural',      'Elza',      'female', 'pt-BR', NULL, 1),
('pt-BR-GiovannaNeural',  'Giovanna',  'female', 'pt-BR', NULL, 1),
('pt-BR-LeticiaNeural',   'Letícia',   'female', 'pt-BR', NULL, 1),
('pt-BR-ManuelaNeural',   'Manuela',   'female', 'pt-BR', NULL, 1),
('pt-BR-ThalitaNeural',   'Thalita',   'female', 'pt-BR', NULL, 1),
('pt-BR-YaraNeural',      'Yara',      'female', 'pt-BR', NULL, 1);

-- Admin padrão (senha: Admin@123)
INSERT INTO `users` (`plan_id`,`role`,`name`,`email`,`password_hash`,`is_active`,`email_verified`) VALUES
(NULL, 'admin', 'Administrador', 'admin@ytauto.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- Configurações do sistema
INSERT INTO `settings` (`key`,`value`,`label`,`group`) VALUES
('site_name',              'YT.AUTO',             'Nome do site',                     'general'),
('multivozes_base_url',    'http://localhost:5050','URL do MultiVozes Engine',         'integrations'),
('multivozes_api_key',     '',                    'API Key do MultiVozes Engine',      'integrations'),
('multivozes_model',       'tts-1',               'Modelo TTS (tts-1 ou tts-1-hd)',   'integrations'),
('asaas_api_key',          '',                    'Asaas API Key',                     'integrations'),
('asaas_sandbox',          '1',                   'Asaas Modo Sandbox (1=sim, 0=prod)','integrations'),
('youtube_api_key',        '',                    'YouTube API Key',                   'integrations'),
('default_voice_male',     'pt-BR-AntonioNeural', 'Voz masculina padrão',             'voices'),
('default_voice_female',   'pt-BR-FranciscaNeural','Voz feminina padrão',             'voices'),
('payment_due_days',       '5',                   'Dias de tolerância pagto',          'financial'),
('smtp_host',              '',                    'SMTP Host',                         'email'),
('smtp_user',              '',                    'SMTP Usuário',                      'email'),
('smtp_pass',              '',                    'SMTP Senha',                        'email'),
('smtp_port',              '587',                 'SMTP Porta',                        'email');
