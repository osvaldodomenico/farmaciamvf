<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        execute("UPDATE " . tableName('doadores') . " SET ativo = 0 WHERE id = ?", [$id]);
        registrarLog($_SESSION['usuario_id'], 'desativou_doador', 'doadores', $id, ['id' => $id]);
        $_SESSION['success'] = 'Doador desativado com sucesso!';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao desativar doador: ' . $e->getMessage();
    }
}

header('Location: listar.php');
exit;
