<?php
namespace App\Config;

use PDO;
use PDOException;

/**
 * Conexão PDO com o PostgreSQL do SaúdeTech.
 *
 * Ordem de configuração:
 *   1. DATABASE_URL (formato do Render: postgresql://usuario:senha@host:porta/banco)
 *   2. DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 *
 * Mudanças em relação ao nutrihealth (MySQL):
 *   - DSN "pgsql:" em vez de "mysql:" e porta padrão 5432
 *   - sslmode (o Render exige SSL em conexões externas)
 *   - search_path apontando para o schema "saudetech", para que as consultas
 *     possam usar só o nome da tabela (vistorias, usuarios...)
 *   - conexão única compartilhada por requisição (singleton)
 */
class Database
{
    private static ?PDO $shared = null;

    private string $host;
    private string $port;
    private string $dbname;
    private string $user;
    private string $pass;
    private string $sslmode;
    private string $schema;

    public function __construct()
    {
        $this->host    = Env::get('DB_HOST', 'localhost');
        $this->port    = Env::get('DB_PORT', '5432');
        $this->dbname  = Env::get('DB_NAME', 'saudetech');
        $this->user    = Env::get('DB_USER', 'postgres');
        $this->pass    = Env::get('DB_PASS', '');
        $this->sslmode = Env::get('DB_SSLMODE', 'prefer');
        $this->schema  = Env::get('DB_SCHEMA', 'saudetech');

        $url = Env::get('DATABASE_URL');
        if ($url) {
            $this->applyUrl($url);
        }
    }

    /** Lê uma URL no formato postgresql://user:pass@host:port/db?sslmode=require */
    private function applyUrl(string $url): void
    {
        // Aceita também o formato JDBC (DBeaver/Java): jdbc:postgresql://host:5432/banco?user=...&password=...
        $url = preg_replace('/^jdbc:/i', '', trim($url));

        $p = parse_url($url);
        if ($p === false || empty($p['host'])) {
            return;
        }
        $this->host   = $p['host'];
        $this->port   = isset($p['port']) ? (string)$p['port'] : '5432';
        $this->user   = isset($p['user']) ? urldecode($p['user']) : $this->user;
        $this->pass   = isset($p['pass']) ? urldecode($p['pass']) : $this->pass;
        $this->dbname = isset($p['path']) ? ltrim($p['path'], '/') : $this->dbname;

        if (!empty($p['query'])) {
            parse_str($p['query'], $q);
            if (!empty($q['sslmode'])) {
                $this->sslmode = (string)$q['sslmode'];
            }
            // Parâmetros no estilo JDBC
            if (!empty($q['user']))     { $this->user = (string)$q['user']; }
            if (isset($q['password']))  { $this->pass = (string)$q['password']; }
        }
        if (empty($q['sslmode']) && str_ends_with($this->host, '.render.com')) {
            // Host externo do Render sem sslmode explícito: SSL é obrigatório
            $this->sslmode = 'require';
        }
    }

    public function getConnection(): PDO
    {
        if (self::$shared !== null) {
            return self::$shared;
        }

        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                $this->host, $this->port, $this->dbname, $this->sslmode
            );

            $pdo = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Nome do schema validado para evitar injeção (só letras, números e _)
            $schema = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->schema) ? $this->schema : 'saudetech';
            $pdo->exec("SET search_path TO {$schema}, public");
            $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
            $pdo->exec("SET client_encoding TO 'UTF8'");

            self::$shared = $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            if (!extension_loaded('pdo_pgsql')) {
                $detail = 'O PHP não está com o driver do PostgreSQL ativo (extensão pdo_pgsql). '
                        . 'No php.ini, remova o ";" de "extension=pdo_pgsql" e "extension=pgsql" e reinicie o Apache.';
            } elseif (Env::get('APP_DEBUG') === '1') {
                $detail = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
                        . '<br><br>Para um diagnóstico passo a passo, abra <a href="' . BASE_URL . '/diagnostico.php">diagnostico.php</a>.';
            } else {
                $detail = 'Verifique as variáveis DB_* (ou DATABASE_URL) no arquivo .env. '
                        . 'Para ver o erro detalhado, coloque APP_DEBUG=1 no .env e abra public/diagnostico.php.';
            }
            die('<h3>Erro ao conectar no banco de dados PostgreSQL</h3><p>' . $detail . '</p>');
        }

        return self::$shared;
    }
}
