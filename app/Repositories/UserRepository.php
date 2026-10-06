<?php
namespace App\Repositories;

use PDO;

/**
 * Tabela saudetech.usuarios
 * (id UUID, nome, email, matricula, senha_hash, perfil, ativo, created_at, updated_at)
 */
class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query(
            "SELECT id, nome, email, matricula, perfil, ativo, created_at
               FROM usuarios
              ORDER BY ativo DESC, nome"
        )->fetchAll();
    }

    public function find(string $id): ?array
    {
        $st = $this->pdo->prepare("SELECT id, nome, email, matricula, perfil, ativo FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT id, nome, email, perfil, ativo, senha_hash FROM usuarios WHERE lower(email) = lower(?)"
        );
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    /** Lista usuários de um perfil (ex.: agentes para o filtro de vistorias). */
    public function byProfile(string $perfil): array
    {
        $st = $this->pdo->prepare("SELECT id, nome FROM usuarios WHERE perfil = ? ORDER BY nome");
        $st->execute([$perfil]);
        return $st->fetchAll();
    }

    public function create(array $u): string
    {
        $st = $this->pdo->prepare(
            "INSERT INTO usuarios (nome, email, matricula, senha_hash, perfil, ativo)
             VALUES (:nome, :email, :matricula, :senha_hash, :perfil, :ativo)
             RETURNING id"
        );
        $st->bindValue(':nome', $u['nome']);
        $st->bindValue(':email', $u['email']);
        $st->bindValue(':matricula', $u['matricula'] ?: null);
        $st->bindValue(':senha_hash', $u['senha_hash']);
        $st->bindValue(':perfil', $u['perfil']);
        $st->bindValue(':ativo', (bool)$u['ativo'], PDO::PARAM_BOOL);
        $st->execute();
        return (string)$st->fetchColumn();
    }

    public function update(string $id, array $u): void
    {
        $st = $this->pdo->prepare(
            "UPDATE usuarios
                SET nome = :nome, email = :email, matricula = :matricula, perfil = :perfil, ativo = :ativo
              WHERE id = :id"
        );
        $st->bindValue(':nome', $u['nome']);
        $st->bindValue(':email', $u['email']);
        $st->bindValue(':matricula', $u['matricula'] ?: null);
        $st->bindValue(':perfil', $u['perfil']);
        $st->bindValue(':ativo', (bool)$u['ativo'], PDO::PARAM_BOOL);
        $st->bindValue(':id', $id);
        $st->execute();
    }

    /**
     * Usuários com vistorias/notificações não podem ser apagados (chave estrangeira).
     * Nesse caso o controller oferece a inativação.
     */
    public function delete(string $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $st->execute([$id]);
    }

    public function emailExists(string $email, ?string $ignoreId = null): bool
    {
        $sql = "SELECT 1 FROM usuarios WHERE lower(email) = lower(?)";
        $params = [$email];
        if ($ignoreId) {
            $sql .= " AND id <> ?";
            $params[] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    public function matriculaExists(string $matricula, ?string $ignoreId = null): bool
    {
        if ($matricula === '') {
            return false;
        }
        $sql = "SELECT 1 FROM usuarios WHERE matricula = ?";
        $params = [$matricula];
        if ($ignoreId) {
            $sql .= " AND id <> ?";
            $params[] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    public function getPasswordHash(string $id): ?string
    {
        $st = $this->pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        $h = $st->fetchColumn();
        return $h ? (string)$h : null;
    }

    public function updatePassword(string $id, string $hash): void
    {
        $st = $this->pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
        $st->execute([$hash, $id]);
    }

    /**
     * Verifica a senha aceitando dois formatos:
     *  1. hash do PHP (bcrypt/argon, gerado por password_hash) — formato padrão do sistema;
     *  2. formato legado do script de carga (seed_saudetech.sql):
     *     md5(senha || 'saudetech_salt'), usado porque o Render não libera o pgcrypto.
     * Quando o formato legado confere, o controller regrava a senha em bcrypt.
     */
    public static function verifyPassword(string $plain, string $hash): bool
    {
        if (password_get_info($hash)['algo'] !== null && password_verify($plain, $hash)) {
            return true;
        }
        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            $salt = getenv('LEGACY_MD5_SALT') ?: 'saudetech_salt';
            return hash_equals(strtolower($hash), md5($plain . $salt));
        }
        return false;
    }

    public static function needsRehash(string $hash): bool
    {
        return password_get_info($hash)['algo'] === null || password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}
