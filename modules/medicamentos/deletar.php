<?php
require_once __DIR__ . '/../../templates/auth.php';

$id = $_GET['id'] ?? 0;

try {
    execute("UPDATE " . tableName('medicamentos') . " SET ativo = 0 WHERE id = ?", [$id]);
    registrarLog($_SESSION['usuario_id'], 'desativou_medicamento', 'medicamentos', $id, ['id' => $id]);
    $_SESSION['success'] = 'Medicamento desativado com sucesso!';
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao desativar medicamento.';
}

header('Location: listar.php');
exit;
