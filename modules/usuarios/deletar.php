<?php
session_start();
require_once '../../config/database.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Verificar permissão (apenas admin)
if ($_SESSION['nivel_acesso'] != 'admin') {
    $_SESSION['error'] = 'Você não tem permissão para executar esta ação.';
    header('Location: listar.php');
    exit();
}

$id = $_GET['id'] ?? 0;

// Buscar usuário
$usuario = fetchOne("SELECT * FROM " . tableName('usuarios') . " WHERE id = ?", [$id]);

if (!$usuario) {
    $_SESSION['error'] = 'Usuário não encontrado.';
    header('Location: listar.php');
    exit();
}

// Não permitir deletar a si mesmo
if ($id == $_SESSION['usuario_id']) {
    $_SESSION['error'] = 'Você não pode desativar seu próprio usuário.';
    header('Location: listar.php');
    exit();
}

try {
    // Soft delete - marcar como inativo
    update(tableName('usuarios'), ['ativo' => 0], 'id = ?', [$id]);
    
    // Registrar log
    registrarLog(
        $_SESSION['usuario_id'], 
        'Desativou usuário', 
        'usuarios', 
        $id,
        $usuario,
        ['ativo' => 0]
    );
    
    $_SESSION['success'] = 'Usuário desativado com sucesso!';
    
} catch (Exception $e) {
    error_log("Erro ao desativar usuário: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao desativar usuário. Tente novamente.';
}

header('Location: listar.php');
exit();
