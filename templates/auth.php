<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Tenta detectar o caminho para login.php
    $current_path = $_SERVER['PHP_SELF'];
    $depth = substr_count($current_path, '/') - 1;
    $base_path = str_repeat('../', $depth);
    if ($depth == 0) $base_path = '';
    
    header("Location: " . $base_path . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Informações do usuário logado
$usuario_id = $_SESSION['usuario_id'];

// Buscar dados atualizados do banco para garantir consistência
$fresh_user = fetchOne("SELECT nome_completo, nivel_acesso, login FROM " . tableName('usuarios') . " WHERE id = ?", [$usuario_id]);

if ($fresh_user) {
    $usuario_nome = $fresh_user['nome_completo'];
    $usuario_nivel = $fresh_user['nivel_acesso'];
    $usuario_login = $fresh_user['login'];
    
    // Atualizar sessão para manter sincronia
    $_SESSION['nome_completo'] = $usuario_nome;
    $_SESSION['nivel_acesso'] = $usuario_nivel;
    $_SESSION['login'] = $usuario_login;
} else {
    // Fallback caso algo aconteça com o banco
    $usuario_nome = $_SESSION['nome_completo'] ?? 'Usuário';
    $usuario_nivel = $_SESSION['nivel_acesso'] ?? 'atendente';
    $usuario_login = $_SESSION['login'] ?? '';
}
