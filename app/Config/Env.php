<?php
namespace App\Config;

/**
 * Carregador simples de arquivo .env (sem dependências externas).
 *
 * - Localmente (XAMPP): lê o arquivo ".env" na raiz do projeto.
 * - No Render/Docker: as variáveis já vêm do ambiente do serviço e têm prioridade
 *   (o .env nunca sobrescreve uma variável que já exista no ambiente).
 */
class Env
{
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Remove aspas opcionais: KEY="valor" ou KEY='valor'
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}
