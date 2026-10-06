<?php
/**
 * Diagnóstico da conexão com o PostgreSQL.
 * Acesse: http://localhost/saudetech-dashboards/public/diagnostico.php
 *
 * Só funciona com APP_DEBUG=1 no .env (por segurança, fica desativado em produção).
 * Nunca exibe a senha.
 */
declare(strict_types=1);

$basePath = dirname(__DIR__);
require $basePath . '/app/Config/Env.php';

use App\Config\Env;

$envFile = $basePath . '/.env';
Env::load($envFile);

if (Env::get('APP_DEBUG') !== '1') {
    http_response_code(404);
    exit('Diagnóstico desativado. Coloque APP_DEBUG=1 no arquivo .env para usar esta página.');
}

$checks = [];
function check(string $titulo, bool $ok, string $detalhe, string $solucao = ''): bool
{
    global $checks;
    $checks[] = compact('titulo', 'ok', 'detalhe', 'solucao');
    return $ok;
}

// 1. Versão do PHP
check('Versão do PHP', PHP_VERSION_ID >= 80000, 'PHP ' . PHP_VERSION,
    'O projeto precisa de PHP 8.0 ou superior.');

// 2. Extensão pdo_pgsql
$driverOk = check('Extensão pdo_pgsql (driver do PostgreSQL)', extension_loaded('pdo_pgsql'),
    extension_loaded('pdo_pgsql') ? 'Carregada' : 'NÃO carregada. Drivers disponíveis: ' . implode(', ', PDO::getAvailableDrivers()),
    'Abra o php.ini (' . (php_ini_loaded_file() ?: 'não encontrado') . '), remova o ";" do início das linhas ' .
    '"extension=pdo_pgsql" e "extension=pgsql", salve e reinicie o Apache no painel do XAMPP.');

// 2b. Versão do libpq (o Render exige SNI, disponível a partir do libpq 14)
if (defined('PGSQL_LIBPQ_VERSION')) {
    $libpq = PGSQL_LIBPQ_VERSION;
    check('Versão do libpq (biblioteca cliente do PostgreSQL)', version_compare($libpq, '14', '>='),
        "libpq $libpq" . (version_compare($libpq, '14', '<') ? ' — não envia SNI; o Render recusa a conexão ("No SNI information found").' : ''),
        'Substitua o libpq.dll do XAMPP por um de versão 14 ou superior (copiado da pasta bin de uma instalação do PostgreSQL 16) ou rode o projeto pelo Docker. Veja o README.');
} else {
    check('Versão do libpq', true, 'Não detectada (a extensão "pgsql" não está ativa; ative extension=pgsql no php.ini para ver a versão).');
}

// 3. Arquivo .env
$envOk = check('Arquivo .env', is_file($envFile), is_file($envFile) ? $envFile : 'Não encontrado em ' . $envFile,
    'Crie o arquivo .env na raiz do projeto (mesma pasta do README.md). No Windows, confira se ele não ficou com o nome ".env.txt".');

// 4. Variáveis lidas
$url  = Env::get('DATABASE_URL');
$host = Env::get('DB_HOST', 'localhost');
$port = Env::get('DB_PORT', '5432');
$db   = Env::get('DB_NAME', 'saudetech');
$user = Env::get('DB_USER', 'postgres');
$pass = Env::get('DB_PASS', '');
$ssl  = Env::get('DB_SSLMODE', 'prefer');
if ($url) {
    $p = parse_url(preg_replace('/^jdbc:/i', '', $url));
    $host = $p['host'] ?? $host;
    $port = isset($p['port']) ? (string)$p['port'] : $port;
    $db   = isset($p['path']) ? ltrim($p['path'], '/') : $db;
    $user = isset($p['user']) ? urldecode($p['user']) : $user;
    $pass = isset($p['pass']) ? urldecode($p['pass']) : $pass;
}
check('Configuração lida', $host !== '' && $pass !== '',
    "host=$host · porta=$port · banco=$db · usuário=$user · senha=" . ($pass === '' ? '(vazia!)' : strlen($pass) . ' caracteres') . " · sslmode=$ssl" . ($url ? ' · (via DATABASE_URL)' : ''),
    'Preencha DB_HOST, DB_USER e DB_PASS no .env. Use o "Hostname" completo da aba Connect do Render (terminando em .render.com).');

// 5. DNS
$ip = gethostbyname($host);
$dnsOk = check('Resolução de nome (DNS)', $ip !== $host || filter_var($host, FILTER_VALIDATE_IP) !== false,
    filter_var($host, FILTER_VALIDATE_IP) ? "$host (endereço IP)" : ($ip !== $host ? "$host → $ip" : "Não foi possível resolver $host"),
    'Verifique o hostname (use o External Hostname do Render, com ".ohio-postgres.render.com") e sua conexão com a internet.');

// 6. Porta TCP
$tcpOk = false;
if ($dnsOk) {
    $t0 = microtime(true);
    $fp = @fsockopen($host, (int)$port, $errno, $errstr, 8);
    $tcpOk = check('Conexão de rede na porta ' . $port, (bool)$fp,
        $fp ? 'Porta acessível (' . round((microtime(true) - $t0) * 1000) . ' ms)' : "Falhou: $errstr ($errno)",
        "A rede está bloqueando a porta $port (comum em redes de faculdades/empresas) ou o banco está suspenso no Render. ' .
        'Teste em outra rede (ex.: roteador do celular) e confira no painel do Render se o banco está \"Available\".");
    if ($fp) fclose($fp);
}

// 7. Login no PostgreSQL
$pdo = null;
if ($driverOk && $tcpOk) {
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=$ssl;connect_timeout=10", $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        check('Login no PostgreSQL', true, 'Conectado como ' . $pdo->query('SELECT current_user')->fetchColumn()
            . ' · ' . $pdo->query('SHOW server_version')->fetchColumn());
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $dica = 'Confira usuário e senha na aba Connect do Render.';
        if (stripos($msg, 'SNI') !== false) $dica = 'A versão do libpq usada pelo seu PHP é anterior à 14 e não envia SNI, que o Render exige. '
            . 'Atualize o libpq do XAMPP (veja o item "Versão do libpq" acima) ou rode o projeto pelo Docker.';
        elseif (stripos($msg, 'password authentication failed') !== false) $dica = 'Usuário ou senha incorretos. Copie novamente o Username e o Password da aba Connect do Render (cuidado com espaços no final).';
        elseif (stripos($msg, 'SSL') !== false) $dica = 'Problema de SSL: mantenha DB_SSLMODE=require. Se persistir, atualize o XAMPP (a libpq antiga pode não suportar o TLS do Render).';
        elseif (stripos($msg, 'does not exist') !== false) $dica = 'O nome do banco (DB_NAME) está errado. Use o "Database" da aba Connect do Render.';
        check('Login no PostgreSQL', false, $msg, $dica);
    }
}

// 8. Schema e tabelas
if ($pdo) {
    $schema = Env::get('DB_SCHEMA', 'saudetech');
    try {
        $st = $pdo->prepare("SELECT has_schema_privilege(:s, 'USAGE')");
        $st->execute(['s' => $schema]);
        $usage = (bool)$st->fetchColumn();
        $counts = [];
        if ($usage) {
            foreach (['usuarios', 'estabelecimentos', 'vistorias', 'itens_vistoria'] as $t) {
                try { $counts[] = "$t: " . $pdo->query("SELECT COUNT(*) FROM \"$schema\".$t")->fetchColumn(); }
                catch (PDOException $e) { $counts[] = "$t: ERRO (" . $e->errorInfo[2] . ')'; $usage = false; }
            }
        }
        check("Acesso ao schema \"$schema\" e às tabelas", $usage,
            $usage ? implode(' · ', $counts) : ($counts ? implode(' · ', $counts) : "O usuário $user não tem permissão no schema $schema (ou o schema não existe)."),
            "Rode os scripts do schema/seed no banco ou conceda acesso: GRANT USAGE ON SCHEMA $schema TO $user; GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA $schema TO $user;");
    } catch (PDOException $e) {
        check("Acesso ao schema \"$schema\"", false, $e->getMessage(), 'Schema inexistente: execute o script saudetech_dashboards_schema.sql.');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico · SaúdeTech</title>
<style>
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f3f5f7; color: #0f172a; margin: 0; padding: 24px 16px; }
  main { max-width: 820px; margin: 0 auto; }
  h1 { font-size: 20px; }
  .item { background: #fff; border: 1px solid #d7dde4; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; }
  .item.fail { border-color: #d03b3b; }
  .t { font-weight: 700; display: flex; gap: 8px; align-items: center; }
  .ok { color: #0a7a0a; } .no { color: #c02f2f; }
  .d { font-size: 14px; color: #475569; margin-top: 4px; word-break: break-word; }
  .s { font-size: 14px; margin-top: 8px; padding: 8px 10px; background: #fff7ed; border-radius: 8px; border: 1px solid #fdba74; }
  .final { font-size: 16px; font-weight: 700; padding: 14px 16px; border-radius: 12px; margin-top: 16px; }
</style>
</head>
<body><main>
<h1>Diagnóstico da conexão — SaúdeTech Dashboards</h1>
<?php $falhou = false; foreach ($checks as $c): ?>
  <div class="item <?= $c['ok'] ? '' : 'fail' ?>">
    <div class="t"><span class="<?= $c['ok'] ? 'ok' : 'no' ?>"><?= $c['ok'] ? '✔ OK' : '✖ FALHOU' ?></span> <?= htmlspecialchars($c['titulo']) ?></div>
    <div class="d"><?= htmlspecialchars($c['detalhe']) ?></div>
    <?php if (!$c['ok'] && $c['solucao']): $falhou = true; ?>
      <div class="s"><strong>Como resolver:</strong> <?= htmlspecialchars($c['solucao']) ?></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<div class="final" style="background:<?= $falhou ? '#fee2e2' : '#dcfce7' ?>">
  <?= $falhou ? 'Corrija o primeiro item marcado como FALHOU e recarregue esta página.'
              : 'Tudo certo! Acesse o sistema em ./ e depois mude APP_DEBUG para 0 no .env.' ?>
</div>
</main></body></html>
