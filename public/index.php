<?php
declare(strict_types=1);

session_start();

$basePath = dirname(__DIR__);

/**
 * BASE_URL é calculada automaticamente a partir do caminho real do "index.php"
 * que atendeu a requisição, então funciona sem configuração tanto:
 * - localmente no XAMPP, numa subpasta (ex.: /saudetech-dashboards/public)
 * - no Render, servida na raiz do domínio (ex.: "")
 */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
define('BASE_URL', rtrim($scriptDir, '/'));

// Autoload PSR-4 simples para o namespace App\
spl_autoload_register(function ($class) use ($basePath) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) === 0) {
        $file = $basePath . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Variáveis de ambiente (.env local; no Render vêm do painel do serviço)
App\Config\Env::load($basePath . '/.env');
date_default_timezone_set('America/Sao_Paulo');

use App\Controllers\UserController;
use App\Controllers\DashboardController;
use App\Controllers\VistoriaController;
use App\Controllers\EstabelecimentoController;

$controllerName = $_GET['controller'] ?? 'dashboard';
$action         = $_GET['action']     ?? 'index';

// Rotas públicas (sem precisar estar logado)
$publicRoutes = [
    'user' => ['login', 'logout'],
];

$isPublic = in_array($action, $publicRoutes[$controllerName] ?? [], true);

if (!$isPublic && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?controller=user&action=login');
    exit;
}

$controllers = [
    'dashboard'       => DashboardController::class,
    'vistoria'        => VistoriaController::class,
    'estabelecimento' => EstabelecimentoController::class,
    'user'            => UserController::class,
];

if (!isset($controllers[$controllerName])) {
    http_response_code(404);
    echo 'Controller não encontrada.';
    exit;
}

$controller = new $controllers[$controllerName]();

// Só permite chamar métodos públicos declarados no próprio controller
if (!method_exists($controller, $action) || !(new ReflectionMethod($controller, $action))->isPublic()
    || str_starts_with($action, '__')) {
    http_response_code(404);
    echo 'Rota não encontrada.';
    exit;
}

$controller->{$action}();
