<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\DashboardRepository;
use PDOException;

/**
 * Página Dashboard: busca os dados no repositório e entrega para a view.
 * (Neste laboratório você NÃO precisa mexer neste arquivo.)
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $repo = new DashboardRepository($this->pdo);

        // Se a consulta do aluno tiver erro de SQL, a página continua abrindo
        // e mostra a mensagem do PostgreSQL no lugar do gráfico.
        $erroMeuIndicador = null;
        try {
            $meuIndicador = $repo->meuIndicador();
        } catch (PDOException $e) {
            $meuIndicador = [];
            $erroMeuIndicador = $e->getMessage();
        }

        $this->render('dashboard/index', [
            'totalVistorias'        => $repo->totalVistorias(),
            'totalEstabelecimentos' => $repo->totalEstabelecimentos(),
            'usuariosPorPerfil'     => $repo->usuariosPorPerfil(),
            'meuIndicador'          => $meuIndicador,
            'erroMeuIndicador'      => $erroMeuIndicador,
        ]);
    }
}
