    </main>
    <footer style="text-align:center;padding:16px;font-size:12px;color:var(--muted);border-top:1px solid var(--border);margin-top:auto;">
      SaúdeTech Dashboards v1.0 · Projeto de Extensão · Vigilância Sanitária de Londrina © <?= date('Y') ?>
    </footer>
  </div>
</div>

<script>
  // ===== TEMA (claro -> escuro -> sistema) =====
  const THEME_KEY = 'st_theme';
  const mm = window.matchMedia('(prefers-color-scheme: dark)');

  function getPref() { try { return localStorage.getItem(THEME_KEY) || 'light'; } catch (e) { return 'light'; } }
  function setPref(v) { try { localStorage.setItem(THEME_KEY, v); } catch (e) {} }

  function applyTheme(pref) {
    const root = document.documentElement;
    const dark = pref === 'dark' || (pref === 'system' && mm.matches);
    root.classList.remove('theme-dark', 'theme-light');
    root.classList.add(dark ? 'theme-dark' : 'theme-light');

    const btn = document.getElementById('btnTheme');
    if (btn) {
      const icon = pref === 'system' ? 'monitor' : (pref === 'dark' ? 'moon' : 'sun');
      btn.innerHTML = '<i data-lucide="' + icon + '"></i>';
    }
    if (window.lucide) lucide.createIcons();
    document.dispatchEvent(new CustomEvent('themechange'));
  }

  mm.addEventListener('change', () => { if (getPref() === 'system') applyTheme('system'); });

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getPref());

    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('overlay');
    const isDesktop = () => window.matchMedia('(min-width: 901px)').matches;
    const closeMobile = () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); };

    document.getElementById('btnSidebar')?.addEventListener('click', () => {
      if (isDesktop()) {
        document.documentElement.classList.toggle('sidebar-collapsed');
      } else if (sidebar.classList.contains('open')) {
        closeMobile();
      } else {
        sidebar.classList.add('open'); overlay.classList.add('visible');
      }
    });
    document.getElementById('btnSidebarClose')?.addEventListener('click', closeMobile);
    overlay?.addEventListener('click', closeMobile);

    document.getElementById('btnTheme')?.addEventListener('click', () => {
      const cur  = getPref();
      const next = cur === 'light' ? 'dark' : cur === 'dark' ? 'system' : 'light';
      setPref(next);
      applyTheme(next);
    });

    // ===== Mensagens via ?msg= (SweetAlert) =====
    const usp = new URLSearchParams(location.search);
    const msg = usp.get('msg');
    if (msg && window.Swal) {
      const map = {
        created:          { icon: 'success', title: 'Registro criado com sucesso!' },
        updated:          { icon: 'success', title: 'Registro atualizado com sucesso!' },
        deleted:          { icon: 'success', title: 'Registro excluído com sucesso!' },
        password_changed: { icon: 'success', title: 'Senha alterada com sucesso!' },
        notfound:         { icon: 'warning', title: 'Registro não encontrado.' },
        forbidden:        { icon: 'error',   title: 'Acesso não permitido.' },
        user_in_use:      { icon: 'warning', title: 'Usuário possui vistorias vinculadas',
                            text: 'Para manter o histórico, edite o usuário e desmarque "Ativo" em vez de excluir.' },
        agendada:         { icon: 'success', title: 'Vistoria agendada!',
                            text: 'Ela aparece no app do agente na próxima vez que ele baixar a agenda.' },
        cancelada:        { icon: 'success', title: 'Vistoria cancelada.' },
        nao_cancelavel:   { icon: 'warning', title: 'Esta vistoria não pode mais ser cancelada',
                            text: 'Só vistorias com status "Agendada" podem ser canceladas.' },
        estab_in_use:     { icon: 'warning', title: 'Estabelecimento possui vistorias vinculadas',
                            text: 'Não é possível excluir um estabelecimento com histórico de vistorias.' }
      };
      Swal.fire(map[msg] || { icon: 'info', title: msg });
      usp.delete('msg');
      const qs = usp.toString();
      history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
    }
  });

  // Confirmação de exclusão (envia um form POST com token CSRF)
  function confirmDelete(formId, texto) {
    Swal.fire({
      title: 'Tem certeza?', text: texto || 'Esta ação não poderá ser desfeita!', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar'
    }).then(r => { if (r.isConfirmed) document.getElementById(formId).submit(); });
  }

  // Filtro rápido de tabelas no cliente (sem acento / minúsculo)
  function norm(s) { return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
  function filterTable(inputId, tableId) {
    const f = norm(document.getElementById(inputId).value);
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(tr => {
      tr.style.display = !f || norm(tr.innerText).includes(f) ? '' : 'none';
    });
  }
</script>
</body>
</html>
