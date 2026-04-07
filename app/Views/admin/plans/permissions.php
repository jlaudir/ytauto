<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="page-header">
  <div><h1>Permissões do Sistema</h1><span class="page-sub">Controle granular de acesso por plano</span></div>
  <a href="<?= base_url('admin/plans') ?>" class="btn btn-ghost">← Planos</a>
</div>

<!-- Current permissions table -->
<div class="table-card" style="margin-bottom:24px">
  <table class="admin-table">
    <thead><tr><th>Chave (key)</th><th>Label</th><th>Grupo</th></tr></thead>
    <tbody>
      <?php
      $groups = [];
      foreach ($permissions as $p) $groups[$p['group']][] = $p;
      foreach ($groups as $gname => $gperms):
      ?>
        <tr style="background:var(--bg3)">
          <td colspan="3" style="font-family:var(--font-mono);font-size:11px;font-weight:700;letter-spacing:2px;color:var(--text3);text-transform:uppercase;padding:8px 16px">
            <?= esc($gname) ?>
          </td>
        </tr>
        <?php foreach ($gperms as $p): ?>
        <tr>
          <td><code style="font-family:var(--font-mono);font-size:12px;color:var(--blue)"><?= esc($p['key']) ?></code></td>
          <td><?= esc($p['label']) ?></td>
          <td><span class="badge badge-blue"><?= esc($p['group']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add new permission -->
<div class="form-card">
  <h3 style="font-family:var(--font-display);font-weight:700;font-size:16px;margin-bottom:20px">
    + Adicionar Nova Permissão
  </h3>
  <form method="POST" action="<?= base_url('admin/permissions/save') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group">
        <label>Chave (key) *</label>
        <input type="text" name="key" required placeholder="ex: videos.export" pattern="[a-z_.]+"/>
        <span class="form-hint">Apenas letras minúsculas, pontos e underscores</span>
      </div>
      <div class="form-group">
        <label>Label (descrição) *</label>
        <input type="text" name="label" required placeholder="ex: Exportar vídeos"/>
      </div>
      <div class="form-group">
        <label>Grupo *</label>
        <input type="text" name="group" required placeholder="ex: Vídeos"/>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">+ Criar Permissão</button>
    </div>
  </form>
</div>

<div class="dash-card mt-20" style="background:rgba(255,222,0,0.05);border-color:rgba(255,222,0,0.2)">
  <div class="dc-header"><h3 style="color:var(--yellow)">ℹ Como usar permissões</h3></div>
  <p style="font-size:14px;color:var(--text2);line-height:1.7">
    As permissões são atribuídas aos planos na tela de edição de cada plano.<br>
    O sistema verifica automaticamente se o plano do usuário tem a permissão necessária antes de executar cada ação.<br>
    <strong>Permissões importantes:</strong>
    <code style="font-size:12px">videos.narrate</code> = acesso ao ElevenLabs,
    <code style="font-size:12px">videos.download</code> = baixar arquivos,
    <code style="font-size:12px">api.access</code> = usar a API REST.
  </p>
</div>
<?= $this->endSection() ?>
