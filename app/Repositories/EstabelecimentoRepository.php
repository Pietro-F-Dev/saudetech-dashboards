<?php
namespace App\Repositories;

use PDO;

/** Tabela saudetech.estabelecimentos */
class EstabelecimentoRepository
{
    public const FIELDS = [
        'razao_social', 'cnpj', 'tipo_atividade', 'endereco', 'bairro', 'cidade',
        'latitude', 'longitude', 'responsavel_nome', 'responsavel_cpf', 'sgf_id_externo',
    ];

    public function __construct(private PDO $pdo) {}

    /** Lista com indicadores por estabelecimento (total de vistorias, última vistoria, interdições). */
    public function all(): array
    {
        return $this->pdo->query(
            "SELECT e.id, e.razao_social, e.cnpj, e.tipo_atividade, e.bairro, e.cidade,
                    COUNT(DISTINCT v.id)            AS total_vistorias,
                    MAX(v.data_agendada)            AS ultima_vistoria,
                    COUNT(DISTINCT i.id)            AS total_interdicoes
               FROM estabelecimentos e
          LEFT JOIN vistorias   v ON v.estabelecimento_id = e.id
          LEFT JOIN interdicoes i ON i.vistoria_id = v.id
           GROUP BY e.id
           ORDER BY e.razao_social"
        )->fetchAll();
    }

    public function find(string $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Tipos de atividade já usados no banco (para filtros). */
    public function tiposAtividade(): array
    {
        return $this->pdo->query(
            "SELECT DISTINCT tipo_atividade FROM estabelecimentos ORDER BY 1"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function cnpjExists(string $cnpj, ?string $ignoreId = null): bool
    {
        $sql = "SELECT 1 FROM estabelecimentos WHERE cnpj = ?";
        $params = [$cnpj];
        if ($ignoreId) {
            $sql .= " AND id <> ?";
            $params[] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    public function create(array $d): string
    {
        $cols = implode(', ', self::FIELDS);
        $vals = ':' . implode(', :', self::FIELDS);
        $st = $this->pdo->prepare("INSERT INTO estabelecimentos ({$cols}) VALUES ({$vals}) RETURNING id");
        $st->execute($this->params($d));
        return (string)$st->fetchColumn();
    }

    public function update(string $id, array $d): void
    {
        $set = implode(', ', array_map(fn($f) => "{$f} = :{$f}", self::FIELDS));
        $st = $this->pdo->prepare("UPDATE estabelecimentos SET {$set} WHERE id = :id");
        $st->execute($this->params($d) + ['id' => $id]);
    }

    public function delete(string $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM estabelecimentos WHERE id = ?");
        $st->execute([$id]);
    }

    private function params(array $d): array
    {
        $p = [];
        foreach (self::FIELDS as $f) {
            $v = $d[$f] ?? null;
            $p[$f] = ($v === '' ? null : $v);
        }
        return $p;
    }
}
