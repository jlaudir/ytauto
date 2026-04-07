<?= $this->extend('layouts/client') ?>
<?= $this->section('content') ?>
<div class="client-page client-narrow" style="max-width:900px">
  <div class="page-hero compact">
    <div>
      <h1>🚀 Escolher Plano</h1>
      <p>Faça upgrade e desbloqueie narração com IA, mais vídeos e muito mais.</p>
    </div>
    <a href="<?= base_url('app/subscription') ?>" class="btn btn-ghost">← Voltar</a>
  </div>

  <!-- Plans Grid -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:32px">
    <?php foreach ($paid_plans as $i => $plan): ?>
    <?php $isCurrent = ($plan['id'] == $current_plan_id); ?>
    <div style="
      background:var(--surface);
      border:2px solid <?= $i === 1 ? 'var(--red)' : 'var(--border)' ?>;
      border-radius:var(--radius);
      padding:28px 24px;
      position:relative;
      display:flex;flex-direction:column;gap:16px;
      <?= $i === 1 ? 'box-shadow:0 0 30px var(--red-glow)' : '' ?>
    ">
      <?php if ($i === 1): ?>
      <div style="position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:3px 14px;border-radius:100px;letter-spacing:1.5px;white-space:nowrap">MAIS POPULAR</div>
      <?php endif; ?>

      <?php if ($isCurrent): ?>
      <div style="position:absolute;top:12px;right:12px;background:var(--green-glow);color:var(--green);border:1px solid rgba(0,232,122,0.3);font-size:10px;font-weight:700;padding:2px 10px;border-radius:100px">ATUAL</div>
      <?php endif; ?>

      <div>
        <div style="font-family:var(--font-display);font-weight:800;font-size:20px;margin-bottom:4px"><?= esc($plan['name']) ?></div>
        <div style="font-size:13px;color:var(--text2)"><?= esc($plan['description']) ?></div>
      </div>

      <div>
        <div style="font-family:var(--font-display);font-weight:800;font-size:34px;color:<?= $i === 1 ? 'var(--red)' : 'var(--text)' ?>">
          R$ <?= number_format($plan['price_monthly'],2,',','.') ?>
          <span style="font-size:14px;font-weight:400;color:var(--text3)">/mês</span>
        </div>
        <?php if ($plan['price_annual'] > 0): ?>
        <div style="font-size:12px;color:var(--green);margin-top:2px">
          ou R$ <?= number_format($plan['price_annual'],2,',','.') ?>/ano
          <span style="color:var(--text3)">(economize <?= round((1 - $plan['price_annual'] / ($plan['price_monthly']*12)) * 100) ?>%)</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Features -->
      <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;flex:1">
        <li style="font-size:13px;color:var(--text2)">
          ✓ <strong><?= $plan['max_videos_month'] ?: '∞' ?></strong> vídeos por mês
        </li>
        <li style="font-size:13px;color:var(--text2)">
          ✓ <strong><?= $plan['max_voices'] ?></strong> vozes disponíveis
        </li>
        <?php if ($plan['has_analytics']): ?>
        <li style="font-size:13px;color:var(--text2)">✓ Analytics de canal</li>
        <?php endif; ?>
        <?php
        $db = \Config\Database::connect();
        $perms = $db->select('p.key')
            ->from('plan_permissions pp')
            ->join('permissions p','p.id=pp.permission_id')
            ->where('pp.plan_id', $plan['id'])
            ->get()->getResultArray();
        $permKeys = array_column($perms,'key');
        ?>
        <?php if (in_array('videos.narrate', $permKeys)): ?>
        <li style="font-size:13px;color:var(--purple)">✓ Narração com IA (MultiVozes)</li>
        <?php endif; ?>
        <?php if (in_array('videos.download', $permKeys)): ?>
        <li style="font-size:13px;color:var(--text2)">✓ Baixar thumbnails e áudios</li>
        <?php endif; ?>
        <?php if (in_array('videos.post_youtube', $permKeys)): ?>
        <li style="font-size:13px;color:var(--text2)">✓ Postar diretamente no YouTube</li>
        <?php endif; ?>
        <?php if ($plan['has_api_access']): ?>
        <li style="font-size:13px;color:var(--blue)">✓ Acesso à API REST</li>
        <?php endif; ?>
        <?php if ($plan['trial_days'] > 0): ?>
        <li style="font-size:13px;color:var(--green)">✓ <?= $plan['trial_days'] ?> dias de teste grátis</li>
        <?php endif; ?>
      </ul>

      <?php if ($isCurrent): ?>
      <div style="text-align:center;padding:12px;background:var(--surface2);border-radius:var(--radius-sm);font-size:13px;color:var(--text3)">
        Você já está neste plano
      </div>
      <?php else: ?>
      <form method="POST" action="<?= base_url('app/upgrade') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>"/>
        <div style="display:flex;gap:8px;margin-bottom:10px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;flex:1">
            <input type="radio" name="billing_cycle" value="monthly" checked style="accent-color:var(--red)"> Mensal
          </label>
          <?php if ($plan['price_annual'] > 0): ?>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;flex:1">
            <input type="radio" name="billing_cycle" value="annual" style="accent-color:var(--red)"> Anual
          </label>
          <?php endif; ?>
        </div>
        <button type="submit" style="
          width:100%;
          background:<?= $i === 1 ? 'linear-gradient(135deg,var(--red),#ff5c20)' : 'var(--surface2)' ?>;
          border:<?= $i === 1 ? 'none' : '1px solid var(--border)' ?>;
          border-radius:var(--radius-sm);
          padding:12px;
          color:<?= $i === 1 ? '#fff' : 'var(--text)' ?>;
          font-family:var(--font-display);
          font-weight:800;font-size:14px;cursor:pointer;
          transition:var(--transition);
          box-shadow:<?= $i === 1 ? '0 4px 16px var(--red-glow)' : 'none' ?>
        ">
          <?= $plan['trial_days'] > 0 ? 'Começar trial grátis →' : 'Escolher ' . esc($plan['name']) . ' →' ?>
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center">
    <p style="font-size:13px;color:var(--text2);line-height:1.7">
      🔒 Pagamento confirmado manualmente pelo suporte &nbsp;·&nbsp;
      ✉ Dúvidas? Entre em contato pelo painel &nbsp;·&nbsp;
      🔄 Cancele a qualquer momento
    </p>
  </div>
</div>
<?= $this->endSection() ?>
