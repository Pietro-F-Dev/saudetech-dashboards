<?php
/**
 * Layout principal (sidebar + topbar). Mesmo visual do nutrihealth, adaptado ao SaúdeTech.
 *
 * Variáveis opcionais definidas pela view antes do include:
 *   $pageTitle, $pageSub, $pageIcon  -> cabeçalho da página
 *   $newLink                         -> link do botão "Novo" (omitido = sem botão)
 */
use App\Core\Auth;
use App\Core\Labels;

$userName          = Auth::name();
$currentController = $_GET['controller'] ?? 'dashboard';
$pageTitle         = $pageTitle ?? 'SaúdeTech';
$pageSub           = $pageSub   ?? '';
$pageIcon          = $pageIcon  ?? 'layout-grid';
$newLink           = $newLink   ?? null;

$nav = [
    ['dashboard',       'dashboard',       'index', 'layout-dashboard', 'Dashboard',        null],
    ['vistoria',        'vistoria',        'index', 'clipboard-check',  'Vistorias',        null],
    ['estabelecimento', 'estabelecimento', 'index', 'store',            'Estabelecimentos', null],
    ['user',            'user',            'index', 'users',            'Usuários',         ['admin']],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= Labels::e($pageTitle) ?> · SaúdeTech Dashboards</title>

  <!-- Aplica o tema salvo ANTES do CSS carregar (evita "piscar") -->
  <script>
    (function () {
      var pref = 'light';
      try { pref = localStorage.getItem('st_theme') || 'light'; } catch (e) {}
      var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      document.documentElement.classList.add(dark ? 'theme-dark' : 'theme-light');
    })();
  </script>

  <!-- Ícones e alerts -->
  <script src="https://unpkg.com/lucide@1.48.0/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>

  <style>
    :root {
      color-scheme: light;
      --bg: #f3f5f7;
      --fg: #0f172a;
      --muted: #64748b;
      --surface: #ffffff;
      --surface-elev: #ffffff;
      --on-surface: #0f172a;
      --primary: #0f766e;
      --primary-2: #0891b2;
      --on-primary: #ffffff;
      --danger: #dc2626;
      --on-danger: #ffffff;
      --hover: #e2e8f0;
      --border: #d7dde4;
      --grid: #e5e7eb;
      --sidebar-w: 260px;
      --topbar-h: 56px;

      /* Paleta dos gráficos (validada para daltonismo) */
      --series-1: #2a78d6;
      --series-2: #eb6834;
      --series-3: #1baf7a;
      --series-muted: #a8a7a0;
      --status-good: #0ca30c;
      --status-warning: #fab219;
      --status-serious: #ec835a;
      --status-critical: #d03b3b;
    }

    .theme-dark {
      color-scheme: dark;
      --bg: #0b1220;
      --fg: #e5e7eb;
      --muted: #94a3b8;
      --surface: #111a2b;
      --surface-elev: #0f172a;
      --on-surface: #e5e7eb;
      --primary: #14b8a6;
      --primary-2: #22d3ee;
      --on-primary: #042f2e;
      --danger: #ef4444;
      --on-danger: #fef2f2;
      --hover: #1e293b;
      --border: #243145;
      --grid: #1f2a3b;

      --series-1: #3987e5;
      --series-2: #d95926;
      --series-3: #199e70;
      --series-muted: #6b6a64;
    }

    * { box-sizing: border-box; }
    html, body {
      margin: 0; padding: 0; min-height: 100%;
      background:
        radial-gradient(circle at top left, rgba(8,145,178,.12), transparent 55%),
        radial-gradient(circle at bottom right, rgba(15,118,110,.12), transparent 55%),
        var(--bg);
      color: var(--fg);
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; height: auto; }
    i[data-lucide], svg.lucide { width: 18px; height: 18px; display: inline-block; vertical-align: middle; }

    .layout { display: flex; min-height: 100dvh; }

    aside.sidebar {
      position: fixed; inset: 0 auto 0 0; width: var(--sidebar-w);
      background: var(--surface-elev); border-right: 1px solid var(--border);
      padding: 14px 12px; display: flex; flex-direction: column; gap: 10px;
      transition: transform .25s ease; z-index: 60;
    }
    .sidebar-header { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 6px; }
    .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; }
    .brand .logo {
      width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff;
    }
    .brand-badge { font-size: 11px; font-weight: 500; color: var(--muted); }

    .nav-label { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin: 10px 6px 4px; }
    .nav-group { display: flex; flex-direction: column; gap: 4px; }
    .nav-item {
      display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px;
      font-size: 14px; color: var(--muted); border: 1px solid transparent;
    }
    .nav-item .label { flex: 1; }
    .nav-item:hover { background: var(--hover); color: var(--fg); }
    .nav-item.active {
      background: linear-gradient(135deg, rgba(15,118,110,.12), rgba(8,145,178,.12));
      border-color: rgba(15,118,110,.45); color: var(--primary); font-weight: 600;
    }

    .sidebar-footer {
      margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border);
      display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--muted);
    }
    .sidebar-footer strong { color: var(--fg); font-size: 13px; }

    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; min-width: 0; }
    html.sidebar-collapsed aside.sidebar { transform: translateX(-100%); }
    html.sidebar-collapsed .main { margin-left: 0; }

    .overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 50; }
    .overlay.visible { opacity: 1; pointer-events: auto; }

    header.topbar {
      position: sticky; top: 0; z-index: 40; height: var(--topbar-h);
      display: flex; align-items: center; gap: 8px; padding: 8px 18px;
      background: var(--surface-elev); border-bottom: 1px solid var(--border);
    }

    .btn {
      display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 12px;
      border-radius: 999px; border: 1px solid var(--border); background: var(--surface-elev);
      color: var(--fg); cursor: pointer; font-family: inherit; white-space: nowrap;
    }
    .btn i[data-lucide], .btn svg.lucide { width: 16px; height: 16px; }
    .btn:not(.btn-primary):not(.btn-danger):hover { background: var(--hover); }
    .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff; border-color: transparent; }
    .btn-primary:hover { filter: brightness(1.07); }
    .btn-danger { background: var(--danger); color: var(--on-danger); border-color: transparent; }
    .btn-danger:hover { filter: brightness(1.07); }
    .btn-sm { padding: 5px 10px; font-size: 12px; }

    .badge {
      font-size: 12px; border-radius: 999px; padding: 3px 10px; background: var(--surface-elev);
      border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 6px; font-weight: 500;
    }

    main.content { padding: 18px 18px 24px; max-width: 1400px; width: 100%; }

    .page-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
    .page-head .page-icon {
      width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, rgba(15,118,110,.15), rgba(8,145,178,.15)); color: var(--primary);
      border: 1px solid rgba(15,118,110,.3);
    }
    .page-title { font-size: 20px; font-weight: 700; }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 2px; }
    @media (min-width: 901px) { #btnSidebarClose { display: none; } }
    .page-actions { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }

    /* ---------- componentes compartilhados ---------- */
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px; min-width: 0; }
    .card-title { display: flex; align-items: center; gap: 8px; font-weight: 700; margin: 0 0 12px; font-size: 15px; }
    .card-sub { color: var(--muted); font-size: 12px; font-weight: 400; margin-left: auto; }
    .grid { display: grid; gap: 14px; }
    .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-2-1 { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); }
    .muted { color: var(--muted); }
    .mt { margin-top: 14px; }

    table.table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table th, .table td { padding: 10px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
    .table th { color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
    .table tr:last-child td { border-bottom: none; }
    .table tbody tr:hover { background: var(--hover); }
    .table .num { text-align: right; font-variant-numeric: tabular-nums; }
    .table-wrap { overflow-x: auto; }

    .pill {
      display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px;
      border: 1px solid var(--border); font-size: 12px; font-weight: 600; line-height: 1.2; white-space: nowrap;
    }
    .pill-info { background: rgba(42,120,214,.12); border-color: rgba(42,120,214,.4); color: #2563c9; }
    .pill-warn { background: rgba(250,178,25,.15); border-color: rgba(217,119,6,.45); color: #b45309; }
    .pill-ok   { background: rgba(12,163,12,.12);  border-color: rgba(12,163,12,.4);  color: #0a7a0a; }
    .pill-done { background: rgba(15,118,110,.12); border-color: rgba(15,118,110,.4); color: var(--primary); }
    .pill-bad  { background: rgba(208,59,59,.12);  border-color: rgba(208,59,59,.45); color: #c02f2f; }
    .theme-dark .pill-info { color: #7fb2f0; }
    .theme-dark .pill-warn { color: #fbbf24; }
    .theme-dark .pill-ok   { color: #4ade80; }
    .theme-dark .pill-bad  { color: #f87171; }

    .form { max-width: 760px; }
    .form label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 12px; }
    .form input[type=text], .form input[type=email], .form input[type=password], .form input[type=date],
    .form select, .form textarea, .filters input, .filters select {
      width: 100%; padding: 9px 11px; border-radius: 8px; border: 1px solid var(--border);
      background: var(--surface-elev); color: var(--fg); font: inherit; font-weight: 400; margin-top: 4px;
    }
    .form .row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
    .form .check { display: flex; align-items: center; gap: 8px; font-weight: 500; }
    .alert-error {
      padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 14px;
      background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.4); color: var(--danger);
    }

    .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 14px; }
    .filters label { font-size: 12px; color: var(--muted); font-weight: 600; min-width: 150px; flex: 1; }
    .filters .actions { display: flex; gap: 8px; }

    .pagination { display: flex; gap: 6px; justify-content: flex-end; align-items: center; margin-top: 12px; font-size: 13px; }

    @media (max-width: 1100px) { .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .grid-3 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 900px) {
      aside.sidebar { transform: translateX(-100%); }
      aside.sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; }
      .grid-2, .grid-3, .grid-2-1 { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .btn .label, .badge .label { display: none; }
      .grid-4 { grid-template-columns: 1fr 1fr; }
      .form .row { grid-template-columns: 1fr; }
      .page-title { font-size: 17px; }
      main.content { padding: 14px 12px 20px; }
    }
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="brand">
        <div class="logo"><i data-lucide="shield-check"></i></div>
        <div>
          <div>SaúdeTech</div>
          <div class="brand-badge">Dashboards · Vigilância Sanitária</div>
        </div>
      </div>
      <button class="btn" id="btnSidebarClose" aria-label="Fechar menu"><i data-lucide="x"></i></button>
    </div>

    <div class="nav-label">Menu</div>
    <nav class="nav-group">
      <?php foreach ($nav as [$ctrl, $route, $act, $icon, $label, $perfis]): ?>
        <?php if ($perfis && !Auth::hasProfile(...$perfis)) continue; ?>
        <a class="nav-item <?= $currentController === $ctrl ? 'active' : '' ?>"
           href="<?= BASE_URL ?>/?controller=<?= $route ?>&action=<?= $act ?>">
          <i data-lucide="<?= $icon ?>"></i><span class="label"><?= $label ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <?php if ($userName): ?>
        <div><strong><?= Labels::e($userName) ?></strong><br><?= Labels::e(Labels::get(Labels::PERFIS, Auth::profile())) ?></div>
        <a href="<?= BASE_URL ?>/?controller=user&action=changePassword" class="nav-item">
          <i data-lucide="key-round"></i><span class="label">Minha senha</span>
        </a>
      <?php endif; ?>
      <div>SaúdeTech &copy; <?= date('Y') ?></div>
    </div>
  </aside>

  <div class="overlay" id="overlay"></div>

  <div class="main">
    <header class="topbar">
      <button class="btn" id="btnSidebar" aria-label="Alternar menu">
        <i data-lucide="menu"></i><span class="label">Menu</span>
      </button>

      <div style="flex:1"></div>

      <button class="btn" id="btnTheme" title="Alternar tema (claro / escuro / sistema)" aria-label="Alternar tema">
        <i data-lucide="sun"></i>
      </button>

      <?php if ($userName): ?>
        <span class="badge"><i data-lucide="user"></i><span class="label"><?= Labels::e($userName) ?></span></span>
      <?php endif; ?>

      <?php if ($newLink): ?>
        <a class="btn btn-primary" href="<?= BASE_URL . $newLink ?>"><i data-lucide="plus"></i><span class="label">Novo</span></a>
      <?php endif; ?>

      <a class="btn btn-danger" href="<?= BASE_URL ?>/?controller=user&action=logout">
        <i data-lucide="log-out"></i><span class="label">Sair</span>
      </a>
    </header>

    <main class="content">
      <div class="page-head">
        <div class="page-icon"><i data-lucide="<?= Labels::e($pageIcon) ?>"></i></div>
        <div>
          <div class="page-title"><?= Labels::e($pageTitle) ?></div>
          <?php if ($pageSub): ?><div class="page-sub"><?= Labels::e($pageSub) ?></div><?php endif; ?>
        </div>
        <?php if (!empty($pageActions)): ?><div class="page-actions"><?= $pageActions ?></div><?php endif; ?>
      </div>
