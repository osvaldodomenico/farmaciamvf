<?php
/**
 * Carregador de variáveis de ambiente (.env)
 * Não requer Composer — implementação simples e direta.
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar comentários e linhas vazias
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Garantir que tem sinal de igual
        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Remover aspas se existirem
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Definir apenas se ainda não estiver definido (permite override via ambiente real)
        if (!isset($_ENV[$name]) && !isset($_SERVER[$name])) {
            putenv("{$name}={$value}");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/**
 * Retorna variável de ambiente com fallback.
 */
function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== null) ? $value : $default;
}
