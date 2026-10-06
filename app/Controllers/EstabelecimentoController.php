<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\EstabelecimentoRepository;
use PDOException;

class EstabelecimentoController extends Controller
{
    private EstabelecimentoRepository $repo;

    /** Perfis que podem cadastrar/alterar estabelecimentos. */
    private const EDITORES = ['admin', 'agente_administrativo', 'supervisora'];

    public function __construct()
    {
        parent::__construct();
        $this->repo = new EstabelecimentoRepository($this->pdo);
    }

    public function index(): void
    {
        $this->render('estabelecimentos/list', [
            'items'   => $this->repo->all(),
            'canEdit' => Auth::hasProfile(...self::EDITORES),
        ]);
    }

    public function create(): void
    {
        $this->requireProfile(...self::EDITORES);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data  = $this->formData();
            $error = $this->validate($data);
            if ($error) {
                $this->render('estabelecimentos/form', ['error' => $error, 'item' => $data, 'isNew' => true]);
                return;
            }
            $this->repo->create($data);
            $this->redirect('controller=estabelecimento&action=index&msg=created');
        }

        $this->render('estabelecimentos/form', ['item' => ['cidade' => 'Londrina'], 'isNew' => true]);
    }

    public function edit(): void
    {
        $this->requireProfile(...self::EDITORES);

        $id   = self::uuidOrNull($_GET['id'] ?? '');
        $item = $id ? $this->repo->find($id) : null;
        if (!$item) {
            $this->redirect('controller=estabelecimento&action=index&msg=notfound');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data  = $this->formData();
            $error = $this->validate($data, $id);
            if ($error) {
                $this->render('estabelecimentos/form', ['error' => $error, 'item' => $data + ['id' => $id], 'isNew' => false]);
                return;
            }
            $this->repo->update($id, $data);
            $this->redirect('controller=estabelecimento&action=index&msg=updated');
        }

        $this->render('estabelecimentos/form', ['item' => $item, 'isNew' => false]);
    }

    public function delete(): void
    {
        $this->requireProfile('admin', 'agente_administrativo');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::checkCsrf()) {
            $this->redirect('controller=estabelecimento&action=index');
        }

        $id = self::uuidOrNull($_POST['id'] ?? '');
        if ($id) {
            try {
                $this->repo->delete($id);
            } catch (PDOException $e) {
                if ($e->getCode() === '23503') { // possui vistorias vinculadas
                    $this->redirect('controller=estabelecimento&action=index&msg=estab_in_use');
                }
                throw $e;
            }
        }
        $this->redirect('controller=estabelecimento&action=index&msg=deleted');
    }

    private function formData(): array
    {
        $d = [];
        foreach (EstabelecimentoRepository::FIELDS as $f) {
            $d[$f] = $this->post($f);
        }
        // aceita vírgula como separador decimal nas coordenadas
        foreach (['latitude', 'longitude'] as $f) {
            $d[$f] = str_replace(',', '.', $d[$f]);
        }
        return $d;
    }

    private function validate(array $d, ?string $ignoreId = null): ?string
    {
        foreach (['razao_social' => 'Razão social', 'cnpj' => 'CNPJ', 'tipo_atividade' => 'Tipo de atividade',
                  'endereco' => 'Endereço', 'cidade' => 'Cidade'] as $f => $label) {
            if ($d[$f] === '') {
                return "O campo {$label} é obrigatório.";
            }
        }
        if (strlen(preg_replace('/\D/', '', $d['cnpj'])) !== 14) {
            return 'CNPJ inválido: informe os 14 dígitos.';
        }
        foreach (['latitude' => 90, 'longitude' => 180] as $f => $max) {
            if ($d[$f] !== '' && (!is_numeric($d[$f]) || abs((float)$d[$f]) > $max)) {
                return 'Coordenada ' . $f . ' inválida.';
            }
        }
        if ($this->repo->cnpjExists($d['cnpj'], $ignoreId)) {
            return 'CNPJ já cadastrado em outro estabelecimento.';
        }
        return null;
    }
}
