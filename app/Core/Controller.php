<?php
namespace App\Core;

use App\Config\Database;
use PDO;

/**
 * Controller base: concentra o que no nutrihealth era repetido em cada controller
 * (conexão, render de view, redirect e checagem de perfil).
 */
abstract class Controller
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->getConnection();
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data);
        include dirname(__DIR__, 2) . "/views/{$view}.php";
    }

    protected function redirect(string $query): void
    {
        header('Location: ' . BASE_URL . '/?' . ltrim($query, '?'));
        exit;
    }

    /** Bloqueia o acesso se o perfil logado não estiver na lista. */
    protected function requireProfile(string ...$perfis): void
    {
        if (!Auth::hasProfile(...$perfis)) {
            http_response_code(403);
            $this->render('partials/forbidden');
            exit;
        }
    }

    protected function post(string $key, string $default = ''): string
    {
        return trim((string)($_POST[$key] ?? $default));
    }

    /** Valida o formato UUID usado como chave primária em todas as tabelas. */
    protected static function uuidOrNull(?string $id): ?string
    {
        $id = trim((string)$id);
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) ? strtolower($id) : null;
    }
}
