<?php
/**
 * Funções de Segurança — CSRF + Rate Limiting
 */

// ===================================================================
// CSRF PROTECTION
// ===================================================================

/**
 * Gera (ou retorna existente) o token CSRF da sessão.
 */
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna campo hidden com token CSRF pronto para colocar em formulários.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida o token CSRF enviado pelo formulário.
 * Encerra com 403 se inválido.
 */
function csrfVerify(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Token de segurança inválido. Recarregue a página e tente novamente.']));
    }
}

// ===================================================================
// RATE LIMITING (arquivo-based, sem Redis)
// ===================================================================

define('RATE_LIMIT_DIR',      __DIR__ . '/../logs/rate_limits/');
define('RATE_LIMIT_MAX',      5);    // máx tentativas
define('RATE_LIMIT_WINDOW',   900);  // 15 minutos em segundos
define('RATE_LIMIT_LOCKOUT',  900);  // bloqueio de 15 minutos

/**
 * Verifica se o IP está bloqueado por excesso de tentativas.
 * Retorna array ['blocked' => bool, 'remaining_seconds' => int, 'attempts' => int]
 */
function rateLimitCheck(string $action, string $ip = ''): array {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Garantir que o diretório existe
    if (!is_dir(RATE_LIMIT_DIR)) {
        mkdir(RATE_LIMIT_DIR, 0700, true);
    }

    $key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $action . '_' . $ip);
    $file = RATE_LIMIT_DIR . $key . '.json';

    $now  = time();
    $data = ['attempts' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $contents = file_get_contents($file);
        if ($contents) {
            $data = json_decode($contents, true) ?: $data;
        }
    }

    // Verificar bloqueio ativo
    if (!empty($data['blocked_until']) && $now < $data['blocked_until']) {
        return [
            'blocked'           => true,
            'remaining_seconds' => $data['blocked_until'] - $now,
            'attempts'          => count($data['attempts']),
        ];
    }

    // Limpar tentativas fora da janela de tempo
    $data['attempts'] = array_filter(
        $data['attempts'],
        fn($t) => ($now - $t) < RATE_LIMIT_WINDOW
    );

    return [
        'blocked'           => false,
        'remaining_seconds' => 0,
        'attempts'          => count($data['attempts']),
    ];
}

/**
 * Registra uma tentativa falha para o IP.
 * Se atingir o limite, aplica bloqueio.
 */
function rateLimitRecord(string $action, string $ip = ''): void {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    if (!is_dir(RATE_LIMIT_DIR)) {
        mkdir(RATE_LIMIT_DIR, 0700, true);
    }

    $key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $action . '_' . $ip);
    $file = RATE_LIMIT_DIR . $key . '.json';
    $now  = time();

    $data = ['attempts' => [], 'blocked_until' => 0];
    if (file_exists($file)) {
        $contents = file_get_contents($file);
        if ($contents) {
            $data = json_decode($contents, true) ?: $data;
        }
    }

    // Limpar tentativas antigas
    $data['attempts'] = array_values(array_filter(
        $data['attempts'],
        fn($t) => ($now - $t) < RATE_LIMIT_WINDOW
    ));

    $data['attempts'][] = $now;

    // Aplicar bloqueio se necessário
    if (count($data['attempts']) >= RATE_LIMIT_MAX) {
        $data['blocked_until'] = $now + RATE_LIMIT_LOCKOUT;
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Limpa os registros de rate limiting para o IP (usar após login bem-sucedido).
 */
function rateLimitClear(string $action, string $ip = ''): void {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    $key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $action . '_' . $ip);
    $file = RATE_LIMIT_DIR . $key . '.json';

    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Formata tempo em segundos para exibição amigável.
 */
function formatRemainingTime(int $seconds): string {
    if ($seconds >= 60) {
        $minutes = ceil($seconds / 60);
        return "{$minutes} minuto" . ($minutes > 1 ? 's' : '');
    }
    return "{$seconds} segundo" . ($seconds > 1 ? 's' : '');
}
