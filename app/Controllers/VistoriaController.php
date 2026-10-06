<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Labels;
use App\Repositories\EstabelecimentoRepository;
use App\Repositories\UserRepository;
use App\Repositories\VistoriaRepository;

class VistoriaController extends Controller
{
    private VistoriaRepository $repo;
    private const PER_PAGE = 25;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new VistoriaRepository($this->pdo);
    }

    public function index(): void
    {
        $filters = $this->filters();
        $total   = $this->repo->count($filters);
        $pages   = max(1, (int)ceil($total / self::PER_PAGE));
        $page    = min(max(1, (int)($_GET['page'] ?? 1)), $pages);

        $this->render('vistorias/list', [
            'rows'    => $this->repo->search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'filters' => $filters,
            'agentes' => Auth::onlyOwnVistorias() ? [] : (new UserRepository($this->pdo))->byProfile('agente_sanitario'),
            'tipos'   => (new EstabelecimentoRepository($this->pdo))->tiposAtividade(),
        ]);
    }

    public function view(): void
    {
        $id = self::uuidOrNull($_GET['id'] ?? '');
        $v  = $id ? $this->repo->find($id) : null;

        if (!$v || (Auth::onlyOwnVistorias() && $v['agente_id'] !== Auth::id())) {
            $this->redirect('controller=vistoria&action=index&msg=notfound');
        }

        $this->render('vistorias/view', [
            'v'            => $v,
            'itens'        => $this->repo->itens($id),
            'assinatura'   => $this->repo->assinatura($id),
            'autos'        => $this->repo->autos($id),
            'interdicoes'  => $this->repo->interdicoes($id),
            'notificacoes' => $this->repo->notificacoes($id),
            'syncLog'      => $this->repo->syncLog($id),
            'canCancel'    => Auth::hasProfile(...self::AGENDADORES) && $v['status'] === 'agendada',
        ]);
    }

    /** Perfis que podem agendar e cancelar vistorias. */
    private const AGENDADORES = ['admin', 'supervisora', 'agente_administrativo'];

    /** Agendar uma nova vistoria (aparece na agenda do app do agente). */
    public function create(): void
    {
        $this->requireProfile(...self::AGENDADORES);

        $estabRepo = new EstabelecimentoRepository($this->pdo);
        $agentes   = array_values(array_filter(
            (new UserRepository($this->pdo))->all(),
            fn($u) => $u['perfil'] === 'agente_sanitario' && $u['ativo']
        ));
        $form = ['estabelecimento_id' => '', 'agente_id' => '', 'data_agendada' => date('Y-m-d')];
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'estabelecimento_id' => self::uuidOrNull($_POST['estabelecimento_id'] ?? '') ?? '',
                'agente_id'          => self::uuidOrNull($_POST['agente_id'] ?? '') ?? '',
                'data_agendada'      => (string)($_POST['data_agendada'] ?? ''),
            ];
            $estab = $form['estabelecimento_id'] ? $estabRepo->find($form['estabelecimento_id']) : null;
            $agenteOk = in_array($form['agente_id'], array_column($agentes, 'id'), true);
            $dataOk = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['data_agendada'])
                      && $form['data_agendada'] >= date('Y-m-d');

            if (!$estab) {
                $error = 'Selecione o estabelecimento.';
            } elseif (!$agenteOk) {
                $error = 'Selecione um agente sanitário ativo.';
            } elseif (!$dataOk) {
                $error = 'Informe uma data a partir de hoje.';
            } elseif (!($template = $this->repo->templateVigente($estab['tipo_atividade']))) {
                $error = 'Não há checklist ativo para o tipo "' . Labels::tipoAtividade($estab['tipo_atividade'])
                       . '". Cadastre o checklist desse tipo no banco antes de agendar.';
            } else {
                $id = $this->repo->agendar($estab['id'], $form['agente_id'], $template, $form['data_agendada']);
                $this->redirect('controller=vistoria&action=view&id=' . $id . '&msg=agendada');
            }
        }

        $this->render('vistorias/create', [
            'estabelecimentos' => $estabRepo->all(),
            'agentes'          => $agentes,
            'form'             => $form,
            'error'            => $error,
        ]);
    }

    public function cancel(): void
    {
        $this->requireProfile(...self::AGENDADORES);
        $id = self::uuidOrNull($_POST['id'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::checkCsrf() || !$id) {
            $this->redirect('controller=vistoria&action=index');
        }
        $ok = $this->repo->cancelar($id);
        $this->redirect('controller=vistoria&action=view&id=' . $id . '&msg=' . ($ok ? 'cancelada' : 'nao_cancelavel'));
    }

    /** Lê e valida os filtros da query string. */
    private function filters(): array
    {
        $date = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$k] ?? '') ? $_GET[$k] : '';
        $status = $_GET['status'] ?? '';
        $sync   = $_GET['sync'] ?? '';

        $f = [
            'de'        => $date('de'),
            'ate'       => $date('ate'),
            'status'    => array_key_exists($status, Labels::STATUS_VISTORIA) ? $status : '',
            'sync'      => array_key_exists($sync, Labels::STATUS_SYNC) ? $sync : '',
            'agente_id' => self::uuidOrNull($_GET['agente_id'] ?? '') ?? '',
            'tipo'      => trim((string)($_GET['tipo'] ?? '')),
            'q'         => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
        ];

        if (Auth::onlyOwnVistorias()) {
            $f['agente_id'] = Auth::id();
        }
        return $f;
    }
}
