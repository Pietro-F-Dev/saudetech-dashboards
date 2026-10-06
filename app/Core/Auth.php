<?php
namespace App\Core;

/**
 * Sessão e perfis de acesso.
 * Perfis vêm do ENUM saudetech.perfil_usuario:
 *   agente_sanitario | supervisora | agente_administrativo | admin
 */
class Auth
{
    public static function id(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function profile(): ?string
    {
        return $_SESSION['user_profile'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::profile() === 'admin';
    }

    public static function hasProfile(string ...$perfis): bool
    {
        return in_array(self::profile(), $perfis, true);
    }

    /** Agente sanitário só enxerga as próprias vistorias. */
    public static function onlyOwnVistorias(): bool
    {
        return self::profile() === 'agente_sanitario';
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['nome'];
        $_SESSION['user_profile'] = $user['perfil'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Token CSRF simples para formulários POST. */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(): bool
    {
        return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''));
    }
}
