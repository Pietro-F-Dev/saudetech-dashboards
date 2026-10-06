<?php use App\Core\Labels; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Entrar · SaúdeTech Dashboards</title>
  <script>
    (function () {
      var pref = 'light';
      try { pref = localStorage.getItem('st_theme') || 'light'; } catch (e) {}
      var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      document.documentElement.classList.add(dark ? 'theme-dark' : 'theme-light');
    })();
  </script>
  <script src="https://unpkg.com/lucide@1.48.0/dist/umd/lucide.min.js"></script>
  <style>
    :root { --bg:#f3f5f7; --fg:#0f172a; --muted:#64748b; --surface:#fff; --border:#d7dde4; --primary:#0f766e; --primary-2:#0891b2; --danger:#dc2626; }
    .theme-dark { color-scheme: dark; --bg:#0b1220; --fg:#e5e7eb; --muted:#94a3b8; --surface:#111a2b; --border:#243145; --primary:#14b8a6; --primary-2:#22d3ee; --danger:#f87171; }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 16px;
      background: radial-gradient(circle at top left, rgba(8,145,178,.18), transparent 55%),
                  radial-gradient(circle at bottom right, rgba(15,118,110,.18), transparent 55%), var(--bg);
      color: var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .box { width: 100%; max-width: 380px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 20px 50px rgba(15,23,42,.12); }
    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
    .logo { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff; }
    h1 { font-size: 19px; margin: 0; }
    .sub { color: var(--muted); font-size: 13px; }
    label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 14px; }
    input { width: 100%; margin-top: 5px; padding: 10px 12px; border-radius: 9px; border: 1px solid var(--border); background: transparent; color: var(--fg); font: inherit; }
    input:focus { outline: 2px solid var(--primary); outline-offset: 1px; }
    button { width: 100%; padding: 11px; border: 0; border-radius: 999px; font: inherit; font-weight: 600; color: #fff; cursor: pointer; background: linear-gradient(135deg, var(--primary), var(--primary-2)); display: flex; gap: 8px; align-items: center; justify-content: center; }
    .error { padding: 10px 12px; border-radius: 9px; margin-bottom: 14px; font-size: 13px; color: var(--danger); background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.35); }
    .foot { margin-top: 18px; text-align: center; font-size: 12px; color: var(--muted); }
    svg.lucide { width: 20px; height: 20px; }
  </style>
</head>
<body>
  <main class="box">
    <div class="brand">
      <div class="logo"><i data-lucide="shield-check"></i></div>
      <div>
        <h1>SaúdeTech Dashboards</h1>
        <div class="sub">Vigilância Sanitária · Londrina/PR</div>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="error"><?= Labels::e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/?controller=user&action=login">
      <label>E-mail
        <input type="email" name="email" required autofocus autocomplete="username" value="<?= Labels::e($email ?? '') ?>">
      </label>
      <label>Senha
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit"><i data-lucide="log-in"></i> Entrar</button>
    </form>

    <div class="foot">Projeto de Extensão · <?= date('Y') ?></div>
  </main>
  <script>window.lucide && lucide.createIcons();</script>
</body>
</html>
