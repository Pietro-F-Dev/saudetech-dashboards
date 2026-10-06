<?php
namespace App\Repositories;

use PDO;

/**
 * Consultas de vistorias e seus dados acessórios
 * (itens_vistoria, fotos_vistoria, assinaturas, autos_vistoria, interdicoes,
 *  notificacoes, sincronizacao_log).
 *
 * As vistorias são criadas pelo app do tablet; aqui a consulta é somente leitura.
 */
class VistoriaRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Monta o WHERE a partir dos filtros da tela.
     * Filtros aceitos: de, ate, status, sync, agente_id, tipo, q (texto livre)
     */
    private function where(array $f, array &$params): string
    {
        $w = [];
        if (!empty($f['de']))        { $w[] = 'v.data_agendada >= :de';        $params['de'] = $f['de']; }
        if (!empty($f['ate']))       { $w[] = 'v.data_agendada <= :ate';       $params['ate'] = $f['ate']; }
        if (!empty($f['status']))    { $w[] = 'v.status = CAST(:status AS status_vistoria)'; $params['status'] = $f['status']; }
        if (!empty($f['sync']))      { $w[] = 'v.sync_status = CAST(:sync AS status_sync)';  $params['sync'] = $f['sync']; }
        if (!empty($f['agente_id'])) { $w[] = 'v.agente_id = CAST(:agente AS uuid)';         $params['agente'] = $f['agente_id']; }
        if (!empty($f['tipo']))      { $w[] = 'e.tipo_atividade = :tipo';      $params['tipo'] = $f['tipo']; }
        if (!empty($f['q'])) {
            // (PDO com prepares nativos não aceita repetir o mesmo placeholder nomeado)
            $w[] = '(e.razao_social ILIKE :q1 OR e.cnpj ILIKE :q2 OR e.bairro ILIKE :q3 OR u.nome ILIKE :q4)';
            $like = '%' . $f['q'] . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        return $w ? 'WHERE ' . implode(' AND ', $w) : '';
    }

    public function count(array $filters): int
    {
        $params = [];
        $where  = $this->where($filters, $params);
        $st = $this->pdo->prepare(
            "SELECT COUNT(*)
               FROM vistorias v
               JOIN estabelecimentos e ON e.id = v.estabelecimento_id
               JOIN usuarios u         ON u.id = v.agente_id
             {$where}"
        );
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function search(array $filters, int $limit = 25, int $offset = 0): array
    {
        $params = [];
        $where  = $this->where($filters, $params);
        $st = $this->pdo->prepare(
            "SELECT v.id, v.data_agendada, v.data_hora_inicio, v.data_hora_fim, v.status, v.sync_status,
                    v.origem_offline,
                    e.razao_social, e.tipo_atividade, e.bairro,
                    u.nome AS agente_nome,
                    (SELECT COUNT(*) FROM itens_vistoria iv WHERE iv.vistoria_id = v.id AND iv.status = 'nao_conforme') AS nao_conformes,
                    (SELECT COUNT(*) FROM itens_vistoria iv WHERE iv.vistoria_id = v.id AND iv.severidade = 'critico')   AS criticos,
                    EXISTS (SELECT 1 FROM interdicoes i WHERE i.vistoria_id = v.id)                                      AS interditado
               FROM vistorias v
               JOIN estabelecimentos e ON e.id = v.estabelecimento_id
               JOIN usuarios u         ON u.id = v.agente_id
             {$where}
           ORDER BY v.data_agendada DESC, v.created_at DESC
              LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Linhas para exportação CSV (mesmos filtros da listagem, sem paginação). */
    public function export(array $filters): array
    {
        return $this->search($filters, 100000, 0);
    }

    public function find(string $id): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT v.*,
                    e.razao_social, e.cnpj, e.tipo_atividade, e.endereco, e.bairro, e.cidade,
                    e.responsavel_nome AS estab_responsavel,
                    u.nome AS agente_nome, u.matricula AS agente_matricula,
                    ct.versao AS checklist_versao,
                    ROUND(EXTRACT(EPOCH FROM (v.data_hora_fim - v.data_hora_inicio)) / 60) AS duracao_minutos
               FROM vistorias v
               JOIN estabelecimentos e     ON e.id = v.estabelecimento_id
               JOIN usuarios u             ON u.id = v.agente_id
               JOIN checklist_templates ct ON ct.id = v.checklist_template_id
              WHERE v.id = ?"
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function itens(string $vistoriaId): array
    {
        $st = $this->pdo->prepare(
            "SELECT iv.id, iv.status, iv.severidade, iv.observacao, iv.justificativa_critica, iv.marcado_em,
                    it.categoria, it.descricao, it.ordem,
                    (SELECT COUNT(*) FROM fotos_vistoria f WHERE f.item_vistoria_id = iv.id) AS fotos
               FROM itens_vistoria iv
               JOIN itens_checklist_template it ON it.id = iv.item_template_id
              WHERE iv.vistoria_id = ?
           ORDER BY it.ordem, it.descricao"
        );
        $st->execute([$vistoriaId]);
        return $st->fetchAll();
    }

    public function assinatura(string $vistoriaId): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM assinaturas WHERE vistoria_id = ?");
        $st->execute([$vistoriaId]);
        return $st->fetch() ?: null;
    }

    public function autos(string $vistoriaId): array
    {
        $st = $this->pdo->prepare("SELECT * FROM autos_vistoria WHERE vistoria_id = ? ORDER BY gerado_em");
        $st->execute([$vistoriaId]);
        return $st->fetchAll();
    }

    public function interdicoes(string $vistoriaId): array
    {
        $st = $this->pdo->prepare(
            "SELECT i.*, it.descricao AS item_descricao
               FROM interdicoes i
               JOIN itens_vistoria iv           ON iv.id = i.item_vistoria_id
               JOIN itens_checklist_template it ON it.id = iv.item_template_id
              WHERE i.vistoria_id = ?
           ORDER BY i.registrada_em"
        );
        $st->execute([$vistoriaId]);
        return $st->fetchAll();
    }

    public function notificacoes(string $vistoriaId): array
    {
        $st = $this->pdo->prepare(
            "SELECT n.*, u.nome AS destinatario_nome
               FROM notificacoes n
               JOIN usuarios u ON u.id = n.destinatario_id
              WHERE n.vistoria_id = ?
           ORDER BY n.created_at"
        );
        $st->execute([$vistoriaId]);
        return $st->fetchAll();
    }

    // ------------------------------------------------------------------ agendamento (painel web)

    /** Checklist vigente (ativo, maior versão) para o tipo de estabelecimento. */
    public function templateVigente(string $tipoAtividade): ?string
    {
        $st = $this->pdo->prepare(
            "SELECT id FROM checklist_templates
              WHERE tipo_estabelecimento = ? AND ativo
           ORDER BY versao DESC LIMIT 1"
        );
        $st->execute([$tipoAtividade]);
        $id = $st->fetchColumn();
        return $id ? (string)$id : null;
    }

    public function agendar(string $estabelecimentoId, string $agenteId, string $templateId, string $data): string
    {
        $st = $this->pdo->prepare(
            "INSERT INTO vistorias (estabelecimento_id, agente_id, checklist_template_id, data_agendada, status, sync_status)
             VALUES (?, ?, ?, ?, 'agendada', 'pendente')
             RETURNING id"
        );
        $st->execute([$estabelecimentoId, $agenteId, $templateId, $data]);
        return (string)$st->fetchColumn();
    }

    /** Só cancela o que ainda não foi feito (agendada). Devolve true se cancelou. */
    public function cancelar(string $id): bool
    {
        $st = $this->pdo->prepare("UPDATE vistorias SET status = 'cancelada' WHERE id = ? AND status = 'agendada'");
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    public function syncLog(string $vistoriaId): array
    {
        $st = $this->pdo->prepare(
            "SELECT * FROM sincronizacao_log WHERE vistoria_id = ? ORDER BY tentativa_numero, executada_em"
        );
        $st->execute([$vistoriaId]);
        return $st->fetchAll();
    }
}
