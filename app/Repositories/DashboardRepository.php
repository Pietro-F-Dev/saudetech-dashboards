<?php
namespace App\Repositories;

use PDO;

/**
 * Consultas SQL do Dashboard.
 *
 * Cada método executa UMA consulta no banco saudetech e devolve as linhas.
 * Formato esperado para virar gráfico (o mesmo do roteiro SQL):
 *   - 1ª coluna  = rótulo (categoria, mês, nome...)
 *   - demais     = números (uma série do gráfico por coluna numérica)
 */
class DashboardRepository
{
    public function __construct(private PDO $pdo) {}

    /** Executa um SELECT e devolve todas as linhas. */
    private function consultar(string $sql): array
    {
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // EXEMPLOS (já prontos — servem de modelo)
    // =========================================================================

    /** Cartão: total de vistorias cadastradas. */
    public function totalVistorias(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM vistorias")->fetchColumn();
    }

    /** Cartão: total de estabelecimentos cadastrados. */
    public function totalEstabelecimentos(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM estabelecimentos")->fetchColumn();
    }

    /** Gráfico de exemplo: quantos usuários existem em cada perfil. */
    public function usuariosPorPerfil(): array
    {
        return $this->consultar("
            SELECT perfil   AS perfil,
                   COUNT(*) AS quantidade
              FROM usuarios
             GROUP BY perfil
             ORDER BY quantidade DESC
        ");
    }

    // =========================================================================
    // ALUNO — SEU INDICADOR
    // Substitua a consulta abaixo pela consulta do indicador que você escolheu
    // (I01 a I12 do roteiro SQL), exatamente como ela funcionou no DBeaver.
    // Regras:
    //   - só SELECT;
    //   - NÃO precisa do "SET search_path" (a conexão já usa o schema saudetech);
    //   - não deixe ";" no final da consulta.
    // =========================================================================
    public function meuIndicador(): array
    {
        return $this->consultar("
            SELECT 'Substitua esta consulta' AS rotulo,
                   0                         AS valor
        ");
    }
}
